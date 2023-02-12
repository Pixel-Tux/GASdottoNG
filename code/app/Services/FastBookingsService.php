<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Movement;

class FastBookingsService extends BaseService
{
	private function sumUpProducts($booking, &$datarow)
	{
		foreach($booking->products_with_friends as $booked) {
			$product_id = $booked->product_id;

			if ($booked->variants->isEmpty() == false) {
				if (isset($datarow['variant_quantity_' . $product_id]) == false) {
					$datarow['variant_quantity_' . $product_id] = [];
				}

				foreach($booked->variants as $bpv) {
					$combo = $bpv->variantsCombo();

					foreach ($combo->values as $val) {
						$variant_id = $val->variant->id;

						if (isset($datarow['variant_selection_' . $variant_id]) == false) {
							$datarow['variant_selection_' . $variant_id] = [];
						}

						$datarow['variant_selection_' . $variant_id][] = $val->id;
					}

					$datarow['variant_quantity_' . $product_id][] = $bpv->true_quantity;
				}
			}
			else {
				if (isset($datarow[$booked->product_id]) == false) {
					$datarow[$booked->product_id] = 0;
				}

				$datarow[$booked->product_id] += $booked->true_quantity;
			}
		}
	}

    /*
        Se definito, $users è un array associativo che contiene come chiavi gli
        ID degli utenti le cui prenotazioni sono da consegnare e come valori gli
        identificativi per i relativi metodi di pagamento di usare.
        Se viene lasciato a NULL, tutte le prenotazioni sono consegnate con il
        metodo di pagamento di default
    */
    public function fastShipping($deliverer, $aggregate, $users = null)
    {
        DB::beginTransaction();

		$service = new BookingsService();

        $default_payment_method = defaultPaymentByType('booking-payment');
        $bookings = $aggregate->bookings;

        if ($users) {
            $users_ids = array_keys($users);
            $bookings = array_filter($bookings, function($booking) use ($users_ids) {
                return in_array($booking->user->id, $users_ids);
            });
        }

        foreach($bookings as $booking) {
			$grand_total = 0;

			foreach ($booking->bookings as $book) {
				$datarow = [
					'action' => 'shipped',
				];

				$this->sumUpProducts($book, $datarow);

				\Log::debug(print_r($datarow, true));

				$shipped_booking = $service->handleBookingUpdate($datarow, $deliverer, $book->order, $booking->user, true);
				$grand_total += $shipped_booking->getValue('effective', true);
			}

            if ($grand_total != 0) {
                $booking->generateReceipt();

				$meta = $users[$booking->user->id] ?? [
	                'date' => date('Y-m-d'),
	                'method' => $default_payment_method,
	            ];

                $movement = Movement::generate('booking-payment', $booking->user, $aggregate, $grand_total);
                $movement->method = $meta['method'];
                $movement->date = $meta['date'];
                $movement->save();
            }
        }

        unset($bookings);
        DB::commit();
    }
}
