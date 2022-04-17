<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

use Auth;
use DB;
use URL;
use Log;

use App\Scopes\RestrictedGAS;
use App\Events\SluggableCreating;
use App\Events\BookingDeleting;

class Booking extends Model
{
    use HasFactory, GASModel, SluggableID, ModifiedTrait, PayableTrait, CreditableTrait, ReducibleTrait, Cachable;

    public $incrementing = false;
    protected $keyType = 'string';
    public $enforced_total = null;

    protected $dispatchesEvents = [
        'creating' => SluggableCreating::class,
        'deleting' => BookingDeleting::class,
    ];

    protected static function boot()
    {
        parent::boot();

        /*
            Questo è per limitare le prenotazioni a quelle effettivamente
            accessibili nel GAS corrente
        */
        static::addGlobalScope('restricted', function(Builder $builder) {
            $builder->whereHas('order', function($query) {
                $query->has('aggregate');
            })->has('user');
        });
    }

    public function user()
    {
        return $this->belongsTo('App\User')->withTrashed();
    }

    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    public function supplier()
    {
        return $this->order->supplier;
    }

    public function products()
    {
        return $this->hasMany('App\BookedProduct')->with(['variants', 'product']);
    }

    public function deliverer()
    {
        return $this->belongsTo('App\User', 'deliverer_id');
    }

    public function payment()
    {
        return $this->belongsTo('App\Movement');
    }

    public function scopeSorted($query)
    {
        /*
            Premesso che questo metodo per ordinare le prenotazioni in base al
            cognome dell'utente è abbastanza deleterio, non funziona neppure nei
            test (la funzione FIELD non esiste in SQLite).
            Dunque la ignoro quando eseguo i test.
        */
        if (env('APP_ENV') == 'testing') {
            return $query;
        }
        else {
            $sorted_users = "'" . join("', '", User::withTrashed()->sorted()->pluck('id')->toArray()) . "'";
            return $query->orderByRaw(DB::raw("FIELD(user_id, $sorted_users)"));
        }
    }

    private function localModifiedValues($id, $with_friends)
    {
        $values = $this->modifiedValues;

        if ($with_friends) {
            foreach($this->friends_bookings as $friend) {
                $values = $values->merge($friend->localModifiedValues($id, true));
            }
        }

        if ($id) {
            $values = $values->filter(function($i) use ($id) {
                return $i->modifier_id == $id;
            });
        }

        return $values;
    }

    public function allModifiedValues($id, $with_friends)
    {
        $values = $this->localModifiedValues($id, false);

        $products = $this->products;
        $values = $products->reduce(function($carry, $product) {
            return $carry->merge($product->modifiedValues);
        }, $values);

        if ($with_friends) {
            foreach($this->friends_bookings as $friend) {
                $values = $values->merge($friend->allModifiedValues($id, true));
            }
        }

        if ($id) {
            $values = $values->filter(function($i) use ($id) {
                return $i->modifier_id == $id;
            });
        }

        return $values;
    }

    /*
        Funzione unica per ottenere i diversi valori della prenotazione: se non
        è ancora stata consegnata calcola al volo i numeri, altrimenti preleva i
        campi salvati sul database al momento della consegna.
    */
    public function getValue($type, $with_friends, $force_recalculate = false)
    {
        $key = sprintf('%s_%s', $type, $with_friends ? 'friends' : 'nofriends');

        if ($force_recalculate) {
            $this->emptyInnerCache($key);
            $this->unsetRelation('products');
        }

        return $this->innerCache($key, function($obj) use ($type, $with_friends) {
            $value = 0;

            /*
                Il totale di quanto prenotato non cambia a seconda che la
                prenotazione sia consegnata o meno
            */
            if (Str::startsWith($type, 'modifier:')) {
                $id = substr($type, strlen('modifier:'));
                if ($id == 'all') {
                    $id = null;
                }

                $modified_values = $obj->allModifiedValues($id, $with_friends);
                $value = ModifiedValue::sumAmounts($modified_values, 0);
            }
            else {
                if ($with_friends) {
                    $products = $obj->products_with_friends;
                }
                else {
                    $products = $obj->products;
                }

                if ($type == 'effective') {
                    $aggregate_data = $obj->order->aggregate->reduxData();

                    $type = $obj->status == 'pending' ? 'booked' : 'delivered';
                    $modified_values = $obj->applyModifiers($aggregate_data, false);

                    if ($with_friends) {
                        foreach($obj->friends_bookings as $friend_booking) {
                            $friend_modified_values = $friend_booking->applyModifiers($aggregate_data, false);
                            $modified_values = $modified_values->merge($friend_modified_values);
                        }
                    }

                    $value = ModifiedValue::sumAmounts($modified_values, $value);
                }

                foreach ($products as $booked) {
                    $booked->setRelation('booking', $obj);
                    $value += $booked->getValue($type);
                }
            }

            return $value;
        });
    }

    public function getBooked($product_id, $fallback = false)
    {
        if (is_object($product_id)) {
            $product_id = $product_id->id;
        }

        $p = $this->products->firstWhere('product_id', $product_id);

        /*
            Se sono in modalità fallback, creo un nuovo oggetto e lo incastro
            nella prenotazione ma senza salvarlo. Verrà poi successivamente
            salvato se e quando sarà necessario (quando sarà accertato che la
            quantità prenotata o consegnata non è 0)
        */
        if (is_null($p) && $fallback == true) {
            $p = new BookedProduct();
            $p->booking_id = $this->id;
            $p->product_id = $product_id;
            $this->products->push($p);
        }

        if (is_null($p) == false) {
            $p->setRelation('booking', $this);
        }

        return $p;
    }

    private function readProductQuantity($product, $field, $friends_bookings)
    {
        $p = $this->getBooked($product);

        if (is_null($p))
            $ret = 0;
        else
            $ret = $p->$field;

        if ($friends_bookings) {
            foreach ($this->friends_bookings as $sub)
                $ret += $sub->readProductQuantity($product, $field, false);
        }

        return $ret;
    }

    public function getBookedQuantity($product, $real = false, $friends_bookings = false)
    {
        return $this->readProductQuantity($product, $real ? 'true_quantity' : 'quantity', $friends_bookings);
    }

    /*
        $real: in caso di prodotti con pezzatura, se == false restituisce la
        quantità eventualmente normalizzata in numeri di pezzi altrimenti
        restituisce la quantità intera.
        In caso di prodotti senza pezzatura, restituisce sempre la quantità
        consegnata non ulteriormente elaborata
    */
    public function getDeliveredQuantity($product, $real = false, $friends_bookings = false)
    {
        return $this->readProductQuantity($product, $real ? 'true_delivered' : 'delivered', $friends_bookings);
    }

    /*
        Valore complessivo di quanto ordinato
    */
    public function getValueAttribute()
    {
        return $this->getValue('booked', false);
    }

    /*
        Valore complessivo di quanto consegnato, diviso tra imponibile e IVA
    */
    public function getDeliveredTaxedAttribute()
    {
        $total = 0;
        $total_vat = 0;

        foreach($this->products_with_friends as $booked_product) {
            list($product_total, $product_total_tax) = $booked_product->deliveredTaxedValue();
            $total += $product_total;
            $total_vat += $product_total_tax;
        }

        return [$total, $total_vat];
    }

    public function getProductsWithFriendsAttribute()
    {
        return $this->innerCache('friends_products', function($obj) {
            $products = $this->products;
            $friends = $this->friends_bookings;

            foreach($friends as $sub) {
                foreach($sub->products as $sub_p) {
                    $master_p = $products->firstWhere('product_id', $sub_p->product_id);
                    if (is_null($master_p)) {
                        $products->push($sub_p);
                    }
                    else {
                        $master_p->quantity += $sub_p->quantity;
                        $master_p->delivered += $sub_p->delivered;
                        $master_p->final_price += $sub_p->final_price;

                        if ($master_p->product->variants->isEmpty() == false) {
                            foreach($sub_p->variants as $sub_variant) {
                                $master_p->variants->push($sub_variant);
                            }
                        }

                        $master_p->modifiedValues = $master_p->modifiedValues->merge($sub_p->modifiedValues);
                    }
                }
            }

            $products = $products->sort(function($a, $b) {
                return $a->product->name <=> $b->product->name;
            });

            return $products;
        });
    }

    public function getFriendsBookingsAttribute()
    {
        return $this->innerCache('friends_bookings', function($obj) {
            $bookings = Booking::where('order_id', $obj->order_id)->whereIn('user_id', $obj->user->friends_with_trashed->pluck('id'))->get();

            foreach($bookings as $b) {
                $b->setRelation('order', $obj->order);
            }

            return $bookings;
        });
    }

    public function getTotalFriendsValueAttribute()
    {
        $ret = 0;

        foreach($this->friends_bookings as $sub) {
            $ret += $sub->getValue('effective', true);
        }

        return $ret;
    }

    public function getShippingPlaceAttribute()
    {
        if ($this->user->isFriend()) {
            return $this->user->parent->shippingplace;
        }
        else {
            return $this->user->shippingplace;
        }
    }

    public static function sortByShippingPlace($bookings, $shipping_place)
    {
        if ($shipping_place == 'all_by_name') {
            usort($bookings, function($a, $b) {
                return $a->user->printableName() <=> $b->user->printableName();
            });
        }
        else if ($shipping_place == 'all_by_place') {
            usort($bookings, function($a, $b) {
                $a_place = $a->shipping_place;
                $b_place = $b->shipping_place;

                if (is_null($a_place) && is_null($b_place)) {
                    return $a->user->printableName() <=> $b->user->printableName();
                }
                else if (is_null($a_place)) {
                    return -1;
                }
                else if (is_null($b_place)) {
                    return 1;
                }
                else {
                    if ($a_place->id != $b_place->id)
                        return $a_place->name <=> $b_place->name;
                    else
                        return $a->user->printableName() <=> $b->user->printableName();
                }
            });
        }
        else {
            $tmp_bookings = [];

            foreach($bookings as $booking) {
                if ($booking->shipping_place->id == $shipping_place) {
                    $tmp_bookings[] = $booking;
                }
            }

            $bookings = $tmp_bookings;

            usort($bookings, function($a, $b) {
                return $a->user->printableName() <=> $b->user->printableName();
            });
        }

        return $bookings;
    }

    public function printableName()
    {
        return $this->order->printableName();
    }

    public function printableHeader()
    {
        $ret = $this->printableName();

        $user = Auth::user();

        $tot = $this->getValue('effective', false);
        $friends_tot = $this->total_friends_value;

        if($tot == 0 && $friends_tot == 0) {
            $message = _i("Non hai partecipato a quest'ordine");
        }
        else {
            $message = _i('Hai ordinato %s', printablePriceCurrency($tot));
            if ($friends_tot != 0) {
                // @phpstan-ignore-next-line
                $message += sprintf(' + %s', printablePriceCurrency($friends_tot));
            }
        }

        $ret .= '<span class="pull-right">' . $message . '</span>';
        return $ret;
    }

    public function getShowURL()
    {
        return route('booking.user.show', ['booking' => $this->order->aggregate_id, 'user' => $this->user_id]);
    }

    public function wipeStatus()
    {
        if ($this->payment) {
            $this->payment->delete();
        }

        $this->status = 'pending';
        $this->payment_id = null;
        $this->deleteModifiedValues();

        $this->unsetRelation('payment');
        $this->unsetRelation('modifiedValues');
        $this->unsetRelation('products');
    }

    public function saveFinalPrices()
    {
        /*
            Qui forzo temporaneamente lo stato della prenotazione per ottenere i
            dati dinamici dai BookedProducts coinvolti
        */

        $keep_status = $this->status;
        $this->status = 'pending';

        foreach($this->products as $p) {
            $p->setRelation('booking', $this);
            $p->final_price = $p->getValue('delivered');
            $p->save();
        }

        $this->status = $keep_status;
    }

    public function involvedModifiers()
    {
        $modifiers = $this->order->involvedModifiers(false);

        if ($this->user->shippingplace) {
            $modifiers = $modifiers->merge($this->user->shippingplace->modifiers);
        }

        return $modifiers->sortBy('priority');
    }

    public function involvedModifiedValues()
    {
        $modifiers = $this->modifiedValues;

        foreach($this->products as $product) {
            $modifiers = $modifiers->merge($product->modifiedValues);
        }

        return $modifiers;
    }

    public function saveModifiers($aggregate_data = null)
    {
        /*
            Qui ripulisco i modificatori eventualmente già salvati, nel caso in
            cui la consegna venga modificata e salvata nuovamente
        */
        $this->deleteModifiedValues();

        $this->calculateModifiers($aggregate_data, true);
    }

    /*
        Questa funzione è da usare in caso di Consegne Manuali senza Quantità,
        il valore viene usato in calculateModifiers()
    */
    public function enforceTotal($total)
    {
        $this->enforced_total = $total;
    }

    public function fixPayment()
    {
        $payment = $this->payment;

        if ($payment) {
            $actual_total = $this->getValue('effective', true);

            if ($payment->amount != $actual_total) {
                if ($payment->type_metadata->altersBalances($payment, 'sender')) {
                    $payment->amount = $actual_total;
                    $payment->save();
                }
                else {
                    $mov = Movement::generate('booking-payment-adjust', $this->user, $this, $actual_total - $payment->amount);
                    $mov->save();
                }
            }
        }
    }

    public function calculateModifiers($aggregate_data = null, $real = true)
    {
        $values = new Collection();

        $modifiers = $this->involvedModifiers();

        /*
            Se non ci sono modificatori coinvolti, evito di fare la riduzione
            dell'intero aggregato.
            TODO: questo potrebbe essere ulteriormente perfezionato
            identificando gli elementi di cui ha bisogno ogni modificatore (la
            prenotazione, l'intero ordine o l'intero aggregato) e ridurre solo
            quelli rilevanti
        */
        if ($modifiers->isEmpty() == false) {
            if (is_null($aggregate_data)) {
                $aggregate = $this->order->aggregate;
                $aggregate_data = $aggregate->reduxData();
            }

            /*
                Se il totale della prenotazione viene forzato manualmente, qui
                definisco esplicitamente il suo valore prima dell'elaborazione
                dei modificatori. In questo modo, questi ultimi saranno
                calcolati in base al totale manuale anziché quello teorico
            */
            if ($this->enforced_total) {
                $aggregate_data->orders[$this->order_id]->bookings[$this->id]->price_delivered = $this->enforced_total;
            }

            if ($real == false) {
                DB::beginTransaction();
            }

            $engine = app()->make('ModifierEngine');

            foreach($modifiers as $modifier) {
                $value = $engine->apply($modifier, $this, $aggregate_data);
                if ($value) {
                    $values = $values->push($value);
                }
            }

            if ($real == false) {
                DB::rollback();
            }
            else {
                $this->unsetRelation('modifiedValues');
            }
        }

        return $values;
    }

    public function applyModifiers($aggregate_data = null, $real = true)
    {
        if ($this->status != 'pending') {
            return $this->allModifiedValues(null, true);
        }
        else {
            return $this->calculateModifiers($aggregate_data, $real);
        }
    }

    public function aggregatedModifiers()
    {
        $modifiers = $this->applyModifiers(null, false);
        return ModifiedValue::aggregateByType($modifiers);
    }

    public function deleteModifiedValues()
    {
        $modified = $this->involvedModifiedValues();
        foreach($modified as $mod) {
            $mod->delete();
        }
    }

    /********************************************************** ModifiedTrait */

    public function getModifiedRelations()
    {
        return (object) [
            'supplier' => $this->order->supplier,
            'user' => $this->user,
        ];
    }

    /********************************************************* ReducibleTrait */

    protected function reduxBehaviour()
    {
        $ret = $this->emptyReduxBehaviour();

        $ret->children = function($item, $filters) {
            return $item->products;
        };

        $ret->optimize = function($item, $child) {
            $child->setRelation('booking', $item);
            return $child;
        };

        $ret->collected = 'products';
        return $ret;
    }

    /************************************************************ SluggableID */

    public function getSlugID()
    {
        return sprintf('%s::%s', $this->order->id, $this->user->id);
    }

    /******************************************************** CreditableTrait */

    public function getBalanceProxy()
    {
        return $this->order->supplier;
    }

    public function balanceFields()
    {
        return [
            'bank' => _i('Saldo'),
        ];
    }

    public static function commonClassName()
    {
        return 'Prenotazione';
    }
}
