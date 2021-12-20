<?php

namespace App\Parameters\MovementType;

class InvoicePayment extends MovementType
{
    public function identifier()
    {
        return 'invoice-payment';
    }

    public function initNew($type)
    {
        $type->name = _i('Pagamento fattura a fornitore');
        $type->sender_type = 'App\Gas';
        $type->target_type = 'App\Invoice';
        $type->visibility = false;
        $type->system = true;

        $type->function = json_encode($this->voidFunctions([
            (object) [
                'method' => 'cash',
                'target' => $this->format(['bank' => 'decrement']),
                'sender' => $this->format(['cash' => 'decrement']),
            ],
            (object) [
                'method' => 'bank',
                'target' => $this->format(['bank' => 'decrement']),
                'sender' => $this->format(['bank' => 'decrement']),
            ]
        ]));

        return $type;
    }
}
