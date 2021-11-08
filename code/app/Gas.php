<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

use Log;

use App\Events\SluggableCreating;

class Gas extends Model
{
    use HasFactory, AttachableTrait, CreditableTrait, PayableTrait, GASModel, SluggableID, Cachable;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'gas';

    protected $dispatchesEvents = [
        'creating' => SluggableCreating::class,
    ];

    public static function commonClassName()
    {
        return 'GAS';
    }

    public function getLogoUrlAttribute()
    {
        if (empty($this->logo))
            return '';
        else
            return url('gas/' . $this->id . '/logo');
    }

    public function users()
    {
        return $this->hasMany('App\User')->orderBy('lastname', 'asc');
    }

    public function suppliers()
    {
        return $this->belongsToMany('App\Supplier')->orderBy('name', 'asc');
    }

    public function aggregates()
    {
        return $this->belongsToMany('App\Aggregate')->orderBy('id', 'desc');
    }

    public function deliveries()
    {
        return $this->belongsToMany('App\Delivery')->orderBy('name', 'asc');
    }

    public function configs()
    {
        return $this->hasMany('App\Config');
    }

    private function handlingConfigs()
    {
        $default_role = Role::where('name', 'Utente')->first();

        return [
            'year_closing' => [
                'default' => date('Y') . '-09-01'
            ],

            'annual_fee_amount' => [
                'default' => 10.00
            ],

            'deposit_amount' => [
                'default' => 10.00
            ],

            'restricted' => [
                'default' => '0'
            ],

            'restrict_booking_to_credit' => [
                'default' => '0'
            ],

            'unmanaged_shipping' => [
                'default' => 0
            ],

            'notify_all_new_orders' => [
                'default' => '0'
            ],

            'auto_user_order_summary' => [
                'default' => '0'
            ],

            'auto_supplier_order_summary' => [
                'default' => '0'
            ],

            'rid' => [
                'default' => (object) [
                    'iban' => '',
                    'id' => '',
                    'org' => ''
                ]
            ],

            'roles' => [
                'default' => (object) [
                    'user' => $default_role ? $default_role->id : -1,
                    'friend' => $default_role ? $default_role->id : -1,
                    'multigas' => $default_role ? $default_role->id : -1
                ]
            ],

            'language' => [
                'default' => 'it_IT'
            ],

            'currency' => [
                'default' => '€'
            ],

            'public_registrations' => [
                'default' => (object) [
                    'enabled' => false,
                    'privacy_link' => 'http://gasdotto.net/privacy',
                    'terms_link' => '',
                    'mandatory_fields' => ['firstname', 'lastname', 'email', 'phone']
                ]
            ],

            'es_integration' => [
                'default' => false,
            ],

            'orders_display_columns' => [
                'default' => ['selection', 'name', 'price', 'quantity', 'total_price', 'quantity_delivered', 'price_delivered', 'notes']
            ],

            'booking_contacts' => [
                'default' => 'none',
            ],

            'paypal' => [
                'default' => (object) [
                    'client_id' => '',
                    'secret' => '',
                    'mode' => 'sandbox'
                ]
            ],

            'satispay' => [
                'default' => (object) [
                    'secret' => ''
                ]
            ],

            'extra_invoicing' => [
                'default' => (object) [
                    'business_name' => '',
                    'taxcode' => '',
                    'vat' => '',
                    'address' => '',
                    'invoices_counter' => 0,
                    'invoices_counter_year' => date('Y'),
                ]
            ],

            'mail_welcome_subject' => [
                'default' => _i("Benvenuto!"),
            ],
            'mail_welcome_body' => [
                'default' => _i("Benvenuto in %[gas_name]!\nIn futuro potrai accedere usando il link qui sotto, lo username \"%[username]\" e la password da te scelta.\n%[gas_login_link]\nUna mail di notifica è stata inviata agli amministratori."),
            ],

            'mail_manual_welcome_subject' => [
                'default' => _i("Benvenuto!"),
            ],
            'mail_manual_welcome_body' => [
                'default' => _i("Sei stato invitato a %[gas_name]!\n\nPer accedere la prima volta clicca il link qui sotto.\n%[gas_access_link]\n\nIn futuro potrai accedere usando quest'altro link, lo username \"%[username]\" e la password che avrai scelto.\n%[gas_login_link]\n"),
            ],

            'mail_password_reset_subject' => [
                'default' => _i("Recupero Password"),
            ],
            'mail_password_reset_body' => [
                'default' => _i("È stato chiesto l'aggiornamento della tua password su GASdotto.\nClicca il link qui sotto per aggiornare la tua password, o ignora la mail se non hai chiesto tu questa operazione.\n%[gas_reset_link]"),
            ],

            'mail_new_order_subject' => [
                'default' => _i("Nuovo Ordine Aperto per %[supplier_name]"),
            ],
            'mail_new_order_body' => [
                'default' => _i("È stato aperto da %[gas_name] un nuovo ordine per il fornitore %[supplier_name].\nPer partecipare, accedi al seguente indirizzo:\n%[gas_booking_link]\nLe prenotazioni verranno chiuse %[closing_date]"),
            ],

            'mail_supplier_summary_subject' => [
                'default' => _i('Prenotazione ordine %[gas_name]'),
            ],
            'mail_supplier_summary_body' => [
                'default' => _i("Buongiorno.\nIn allegato trova - in duplice copia, PDF e CSV - la prenotazione dell'ordine da parte di %[gas_name].\nPer segnalazioni, può rivolgersi ai referenti in copia a questa mail.\nGrazie."),
            ],

            'mail_receipt_subject' => [
                'default' => _i("Nuova fattura da %[gas_name]"),
            ],
            'mail_receipt_body' => [
                'default' => _i("In allegato l'ultima fattura da %[gas_name]")
            ],
        ];
    }

    public function getConfig($name)
    {
        foreach ($this->configs as $conf) {
            if ($conf->name == $name) {
                return $conf->value;
            }
        }

        $defined = self::handlingConfigs();
        if (!isset($defined[$name])) {
            Log::error(_i('Configurazione GAS non prevista'));
            return '';
        }
        else {
            $this->setConfig($name, $defined[$name]['default']);
            $this->load('configs');
            return $this->getConfig($name);
        }
    }

    public function setConfig($name, $value)
    {
        if (is_object($value) || is_array($value))
            $value = json_encode($value);

        foreach ($this->configs as $conf) {
            if ($conf->name == $name) {
                $conf->value = $value;
                $conf->save();
                return;
            }
        }

        $conf = new Config();
        $conf->name = $name;
        $conf->value = $value;
        $conf->gas_id = $this->id;
        $conf->save();
    }

    public function getRidAttribute()
    {
        return (array) json_decode($this->getConfig('rid'));
    }

    public function getRolesAttribute()
    {
        return (array) json_decode($this->getConfig('roles'));
    }

    public function getRestrictBookingToCreditAttribute()
    {
        return $this->getConfig('restrict_booking_to_credit') == '1';
    }

    public function getUnmanagedShippingAttribute()
    {
        return $this->getConfig('unmanaged_shipping') == '1';
    }

    public function getNotifyAllNewOrdersAttribute()
    {
        return $this->getConfig('notify_all_new_orders') == '1';
    }

    public function getAutoUserOrderSummaryAttribute()
    {
        return $this->getConfig('auto_user_order_summary') == '1';
    }

    public function getAutoSupplierOrderSummaryAttribute()
    {
        return $this->getConfig('auto_supplier_order_summary') == '1';
    }

    public function getRestrictedAttribute()
    {
        return $this->getConfig('restricted') == '1';
    }

    public function getLanguageAttribute()
    {
        return $this->getConfig('language');
    }

    public function getCurrencyAttribute()
    {
        return $this->getConfig('currency');
    }

    public function getPublicRegistrationsAttribute()
    {
        return (array) json_decode($this->getConfig('public_registrations'));
    }

    public function getEsIntegrationAttribute()
    {
        return $this->getConfig('es_integration') == '1';
    }

    public function getOrdersDisplayColumnsAttribute()
    {
        return (array) json_decode($this->getConfig('orders_display_columns'));
    }

    public function getBookingContactsAttribute()
    {
        return $this->getConfig('booking_contacts');
    }

    public function getPaypalAttribute()
    {
        return (array) json_decode($this->getConfig('paypal'));
    }

    public function getSatispayAttribute()
    {
        return (array) json_decode($this->getConfig('satispay'));
    }

    public function getExtraInvoicingAttribute()
    {
        return (array) json_decode($this->getConfig('extra_invoicing'));
    }

    public function getAnnualFeeAmountAttribute()
    {
        return $this->getConfig('annual_fee_amount');
    }

    public function getDepositAmountAttribute()
    {
        return $this->getConfig('deposit_amount');
    }

    public function nextInvoiceNumber()
    {
        $status = $this->extra_invoicing;
        $now = date('Y');
        $year = $status['invoices_counter_year'];

        if ($now == $year) {
            $ret = $status['invoices_counter'] + 1;
        }
        else {
            $ret = 1;
            $status['invoices_counter_year'] = $now;
        }

        $status['invoices_counter'] = $ret;
        $this->setConfig('extra_invoicing', $status);

        return sprintf('%s/%s', $ret, $now);
    }

    public function hasFeature($name)
    {
        switch($name) {
            case 'shipping_places':
                return ($this->deliveries->isEmpty() == false);
            case 'rid':
                return !empty($this->rid['iban']);
            case 'paypal':
                return !empty($this->paypal['client_id']);
            case 'satispay':
                return !empty($this->satispay['secret']);
            case 'extra_invoicing':
                return (!empty($this->extra_invoicing['taxcode']) || !empty($this->extra_invoicing['vat']));
            case 'public_registrations':
                return $this->public_registrations['enabled'];
            case 'auto_aggregates':
                return Aggregate::has('orders', '>=', Aggregate::aggregatesConvenienceLimit())->count() > 3;
        }

        return false;
    }

    /*************************************************************** GASModel */

    public function getShowURL()
    {
        return route('multigas.show', $this->id);
    }

    /******************************************************** AttachableTrait */

    protected function requiredAttachmentPermission()
    {
        return 'gas.config';
    }

    /******************************************************** CreditableTrait */

    public function virtualBalances()
    {
        return $this->innerCache('enforced_contacts', function($obj) {
            $suppliers_balance = 0;
            $users_balance = 0;

            foreach($obj->suppliers as $supplier) {
                $suppliers_balance += $supplier->current_balance_amount;
            }

            foreach($obj->users as $user) {
                $users_balance += $user->current_balance_amount;
            }

            return [
                'suppliers' => (object) [
                    'label' => _i('Fornitori'),
                    'value' => $suppliers_balance,
                ],
                'users' => (object) [
                    'label' => _i('Utenti'),
                    'value' => $users_balance,
                ],
            ];
        });
    }

    public function balanceFields()
    {
        $ret = [
            'bank' => _i('Conto Corrente'),
            'cash' => _i('Cassa Contanti'),
            'gas' => _i('GAS'),
            'deposits' => _i('Cauzioni'),
        ];

        $gas = currentAbsoluteGas();

        if ($gas->hasFeature('paypal')) {
            $ret['paypal'] = _i('PayPal');
        }

        if ($gas->hasFeature('satispay')) {
            $ret['satispay'] = _i('Satispay');
        }

        return $ret;
    }
}
