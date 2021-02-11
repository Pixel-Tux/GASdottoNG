<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Auth;
use Log;

use App\Gas;
use App\Supplier;
use App\Aggregate;
use App\Delivery;
use App\Role;

use App\Services\UsersService;
use App\Exceptions\AuthException;
use App\Exceptions\IllegalArgumentException;

class MultiGasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        if ($user->can('gas.multi', $user->gas) == false) {
            abort(503);
        }

        $groups = [];

        foreach($user->roles as $role) {
            if ($role->enabledAction('gas.multi')) {
                foreach ($role->applications() as $obj)
                    if (get_class($obj) == 'App\\Gas')
                        $groups[] = $obj;
            }
        }

        return view('pages.multigas', ['groups' => $groups]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->can('gas.multi', $user->gas) == false) {
            abort(503);
        }

        try {
            DB::beginTransaction();

            $user_service = new UsersService();
            $admin = $user_service->store($request->only('username', 'firstname', 'lastname', 'password', 'enforce_password_change'));

            $gas = new Gas();
            $gas->name = $request->input('name');
            $gas->save();

            /*
                Copio le configurazioni del GAS attuale su quello nuovo
            */
            foreach($user->gas->configs as $config) {
                $new = $config->replicate();
                $new->gas_id = $gas->id;
                $new->save();
            }

            /*
                Aggancio il nuovo GAS all'utente corrente, affinché lo possa
                manipolare
            */
            $roles = Role::havingAction('gas.multi');
            foreach($roles as $role)
                $user->addRole($role, $gas);

            /*
                Aggancio il nuovo utente amministratore al nuovo GAS (di default
                verrebbe assegnato al GAS corrente)
            */
            $admin->gas_id = $gas->id;
            $admin->save();

            $roles = [];

            $target_role = $user->gas->roles['multigas'] ?? -1;
            if ($target_role != -1) {
                $role = Role::find($target_role);
                if ($role) {
                    $roles = [$role];
                }
            }

            if (empty($roles)) {
                $roles = Role::havingAction('gas.permissions');
            }

            foreach($roles as $role) {
                $admin->addRole($role, $gas);
            }

            return $this->successResponse([
                'id' => $gas->id,
                'name' => $gas->printableName(),
                'header' => $gas->printableHeader(),
                'url' => route('multigas.show', $gas->id)
            ]);
        }
        catch (AuthException $e) {
            abort($e->status());
        }
        catch (IllegalArgumentException $e) {
            return $this->errorResponse($e->getMessage(), $e->getArgument());
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $gas = Gas::findOrFail($id);

        if ($user->can('gas.multi', $gas) == false) {
            abort(503);
        }

        return view('multigas.edit', ['gas' => $gas]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $gas = Gas::findOrFail($id);

        if ($user->can('gas.multi', $gas) == false) {
            abort(503);
        }

        /*
            Il modello User ha uno scope globale per manipolare solo gli utenti
            del GAS locale, ma qui serve fare esattamente il contrario (ovvero:
            manipolare solo gli utenti del GAS selezionato)
        */
        foreach($gas->users()->withoutGlobalScopes()->withTrashed()->get() as $u) {
            $u->forceDelete();
        }

        foreach($gas->configs as $c) {
            $c->delete();
        }

        $gas->suppliers()->sync([]);
        $gas->aggregates()->sync([]);
        $gas->deliveries()->sync([]);

        $gas->delete();
        return $this->successResponse();
    }

    public function attach(Request $request)
    {
        DB::beginTransaction();

        $user = Auth::user();
        $gas_id = $request->input('gas');
        $gas = Gas::findOrFail($gas_id);

        if ($user->can('gas.multi', $gas) == false) {
            abort(503);
        }

        $target_id = $request->input('target_id');
        $target_type = $request->input('target_type');

        switch($target_type) {
            case 'supplier':
                $gas->suppliers()->attach($target_id);
                break;

            case 'aggregate':
                $gas->aggregates()->attach($target_id);
                break;

            case 'delivery':
                $gas->deliveries()->attach($target_id);
                break;
        }

        return $this->successResponse();
    }

    public function detach(Request $request)
    {
        DB::beginTransaction();

        $user = Auth::user();
        $gas_id = $request->input('gas');
        $gas = Gas::findOrFail($gas_id);

        if ($user->can('gas.multi', $gas) == false) {
            abort(503);
        }

        $target_id = $request->input('target_id');
        $target_type = $request->input('target_type');

        switch($target_type) {
            case 'supplier':
                $gas->suppliers()->detach($target_id);
                break;

            case 'aggregate':
                $gas->aggregates()->detach($target_id);
                break;

            case 'delivery':
                $gas->deliveries()->detach($target_id);
                break;
        }

        return $this->successResponse();
    }
}
