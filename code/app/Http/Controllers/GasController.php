<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

use Auth;
use DB;
use Log;

use App\Role;
use App\Gas;
use App\User;
use App\Currency;

class GasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['getLogo']]);

        $this->commonInit([
            'reference_class' => 'App\\Gas'
        ]);
    }

    public function index()
    {
        $user = Auth::user();
        return redirect()->route('gas.edit', $user->gas->id);
    }

    public function show()
    {
        $user = Auth::user();
        return redirect()->route('gas.edit', $user->gas->id);
    }

    public function getLogo($id)
    {
        $gas = Gas::findOrFail($id);
        return downloadFile($gas, 'logo');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $gas = Gas::findOrFail($id);
        if ($user->can('gas.config', $gas) == false) {
            abort(503);
        }

        return view('pages.gas', ['gas' => $gas]);
    }

    private function configGeneral($gas, $request)
    {
        if ($request->hasFile('logo')) {
            saveFile($request->file('logo'), $gas, 'logo');
        }

        $gas->name = $request->input('name');
        $gas->email = $request->input('email');
        $gas->message = $request->input('message');
        $gas->setConfig('restricted', $request->has('restricted') ? '1' : '0');
        $gas->setConfig('language', $request->input('language'));
        $gas->setConfig('currency', $request->input('currency'));
    }

    private function configBanking($gas, $request)
    {
        $gas->setConfig('year_closing', decodeDateMonth($request->input('year_closing')));
        $gas->setConfig('annual_fee_amount', $request->input('annual_fee_amount', 0));
        $gas->setConfig('deposit_amount', $request->input('deposit_amount', 0));
        $gas->setConfig('auto_fee', $request->has('auto_fee'));

        if ($request->has('enable_rid')) {
            $rid_info = (object) [
                'iban' => $request->input('rid->iban'),
                'id' => $request->input('rid->id'),
                'org' => $request->input('rid->org'),
            ];
        }
        else {
            $rid_info = (object) [
                'iban' => '',
                'id' => '',
                'org' => '',
            ];
        }

        $gas->setConfig('rid', $rid_info);

        if ($request->has('enable_paypal')) {
            $paypal_info = (object) [
                'client_id' => $request->input('paypal->client_id'),
                'secret' => $request->input('paypal->secret'),
                'mode' => $request->input('paypal->mode'),
            ];
        }
        else {
            $paypal_info = (object) [
                'client_id' => '',
                'secret' => '',
                'mode' => 'sandbox',
            ];
        }

        $gas->setConfig('paypal', $paypal_info);

        if ($request->has('enable_satispay')) {
            $satispay_info = (object) [
                'secret' => $request->input('satispay->secret')
            ];
        }
        else {
            $satispay_info = (object) [
                'secret' => ''
            ];
        }

        $gas->setConfig('satispay', $satispay_info);

        if ($request->has('enable_integralces')) {
            $integralces_info = (object) [
                'enabled' => true,
                'identifier' => $request->input('integralces->identifier'),
            ];

            $integralces_currency = Currency::where('context', 'integralces')->first();
            if (is_null($integralces_currency)) {
                $integralces_currency = new Currency();
                $integralces_currency->context = 'integralces';
                $integralces_currency->symbol = $request->input('integralces->symbol');
            }

            $integralces_currency->enabled = true;
            $integralces_currency->save();
        }
        else {
            $integralces_info = (object) [
                'enabled' => false,
                'identifier' => '',
            ];

            $integralces_currency = Currency::where('context', 'integralces')->first();
            if ($integralces_currency) {
                $integralces_currency->enabled = false;
                $integralces_currency->save();
            }
        }

        $gas->setConfig('integralces', $integralces_info);

        if ($request->has('enable_extra_invoicing')) {
            $invoicing_info = $gas->extra_invoicing;
            $invoicing_info['business_name'] = $request->input('extra_invoicing->business_name');
            $invoicing_info['taxcode'] = $request->input('extra_invoicing->taxcode');
            $invoicing_info['vat'] = $request->input('extra_invoicing->vat');
            $invoicing_info['address'] = $request->input('extra_invoicing->address');

            $reset_counter = $request->input('extra_invoicing->invoices_counter');
            if (!empty($reset_counter))
                $invoicing_info['invoices_counter'] = $reset_counter;
        }
        else {
            $invoicing_info = [
                'business_name' => '',
                'taxcode' => '',
                'vat' => '',
                'address' => '',
                'invoices_counter' => 0
            ];
        }

        $gas->setConfig('extra_invoicing', $invoicing_info);
    }

    private function configUsers($gas, $request)
    {
        if ($request->has('enable_public_registrations')) {
            $registrations_info = (object) [
                'enabled' => true,
                'privacy_link' => $request->input('public_registrations->privacy_link', ''),
                'terms_link' => $request->input('public_registrations->terms_link', ''),
                'mandatory_fields' => Arr::wrap($request->input('public_registrations->mandatory_fields', [])),
            ];
        }
        else {
            $registrations_info = (object) [
                'enabled' => false,
                'privacy_link' => '',
                'terms_link' => '',
                'mandatory_fields' => ['firstname', 'lastname', 'email', 'phone'],
            ];
        }

        $gas->setConfig('public_registrations', $registrations_info);
    }

    private function configOrders($gas, $request)
    {
        $gas->setConfig('restrict_booking_to_credit', $request->has('restrict_booking_to_credit') ? '1' : '0');
        $gas->setConfig('unmanaged_shipping', $request->has('unmanaged_shipping') ? '1' : '0');
        $gas->setConfig('booking_contacts', $request->input('booking_contacts'));
        $gas->setConfig('orders_display_columns', $request->input('orders_display_columns'));
    }

    private function configMails($gas, $request)
    {
        $gas->setConfig('notify_all_new_orders', $request->has('notify_all_new_orders') ? '1' : '0');
        $gas->setConfig('auto_user_order_summary', $request->has('auto_user_order_summary') ? '1' : '0');
        $gas->setConfig('auto_supplier_order_summary', $request->has('auto_supplier_order_summary') ? '1' : '0');

        foreach(systemParameters('MailTypes') as $identifier => $metadata) {
            if ($request->has("custom_mails_${identifier}_subject")) {
                $gas->setConfig("mail_${identifier}", (object) [
                    'subject' => $request->input('custom_mails_' . $identifier . '_subject', ''),
                    'body' => $request->input('custom_mails_' . $identifier . '_body', ''),
                ]);
            }
        }
    }

    private function configRoles($gas, $request)
    {
        $conf = (object) [
            'user' => $request->input('roles->user'),
            'friend' => $request->input('roles->friend'),
            'multigas' => $request->input('roles->multigas'),
        ];

        $old_friend_role = $gas->roles['friend'];
        $update_users = ($conf->friend != $old_friend_role);

        $gas->setConfig('roles', $conf);

        /*
            Se il ruolo "amico" viene cambiato, cambio effettivamente
            gli utenti coinvolti
        */
        if ($update_users) {
            $friends = User::whereNotNull('parent_id')->get();

            foreach($friends as $friend) {
                $friend->removeRole($old_friend_role, $gas);
                $friend->addRole($conf->friend, $gas);
            }
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        $user = Auth::user();
        $gas = Gas::findOrFail($id);

        if ($user->can('gas.config', $gas) == false) {
            return $this->errorResponse(_i('Non autorizzato'));
        }

        $group = $request->input('group');

        switch($group) {
            case 'general':
                $this->configGeneral($gas, $request);
                break;

            case 'banking':
                $this->configBanking($gas, $request);
                break;

            case 'users':
                $this->configUsers($gas, $request);
                break;

            case 'orders':
                $this->configOrders($gas, $request);
                break;

            case 'mails':
                $this->configMails($gas, $request);
                break;

            case 'import':
                $gas->setConfig('es_integration', $request->has('es_integration') ? '1' : '0');
                break;

            case 'roles':
                $this->configRoles($gas, $request);
                break;
        }

        $gas->save();
        return $this->successResponse();
    }

    public function databaseDump(Request $request)
    {
        $user = $request->user();
        if ($user->can('gas.config', $user->gas) == false) {
            abort(503);
        }

        $filepath = sprintf('%s/dump_%s', sys_get_temp_dir(), Str::random(20));

        switch(env('DB_CONNECTION')) {
            case 'mysql':
                \Spatie\DbDumper\Databases\MySql::create()->setDbName(env('DB_DATABASE'))->setUserName(env('DB_USERNAME'))->setPassword(env('DB_PASSWORD'))->dumpToFile($filepath);
                break;

            case 'pgsql':
                \Spatie\DbDumper\Databases\PostgreSql::create()->setDbName(env('DB_DATABASE'))->setUserName(env('DB_USERNAME'))->setPassword(env('DB_PASSWORD'))->dumpToFile($filepath);
                break;

            default:
                Log::error('Formato database non supportato');
                exit();
        }

        return response()->download($filepath, 'database_gasdotto_' . date('Y_m_d') . '.sql')->deleteFileAfterSend();
    }
}
