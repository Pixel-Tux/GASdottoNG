<?php

use App\Balance;
use App\Category;
use App\Gas;
use App\Measure;
use App\Notification;
use App\User;
use App\Role;
use App\VatRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->delete();
        DB::table('password_resets')->delete();
        DB::table('configs')->delete();
        DB::table('gas')->delete();
        DB::table('suppliers')->delete();
        DB::table('products')->delete();
        DB::table('orders')->delete();
        DB::table('aggregates')->delete();
        DB::table('variant_values')->delete();
        DB::table('variants')->delete();
        DB::table('categories')->delete();
        DB::table('measures')->delete();
        DB::table('deliveries')->delete();
        DB::table('notifications')->delete();
        DB::table('bookings')->delete();
        DB::table('booked_products')->delete();
        DB::table('booked_product_variants')->delete();
        DB::table('movement_types')->delete();
        DB::table('movements')->delete();
        DB::table('contacts')->delete();
        DB::table('comments')->delete();

        $gas = Gas::create([
            'id' => str_slug('Senza Nome'),
            'name' => 'Senza Nome',
        ]);

        $balance = Balance::create([
            'target_id' => $gas->id,
            'target_type' => get_class($gas),
            'bank' => 0,
            'cash' => 0,
            'suppliers' => 0,
            'deposits' => 0,
            'date' => date('Y-m-d', time())
        ]);

        $admin_role = Role::create([
            'name' => 'Amministratore',
            'actions' => 'gas.access,gas.permissions,gas.config,supplier.view,supplier.add,users.admin,users.movements,movements.admin,movements.types,categories.admin,measures.admin,gas.statistics,notifications.admin'
        ]);

        $user_role = Role::create([
            'name' => 'Utente',
            'actions' => 'users.self,users.view,supplier.view,supplier.book',
            'always' => true,
            'parent_id' => $admin_role->id
        ]);

        $referrer_role = Role::create([
            'name' => 'Referente',
            'actions' => 'supplier.modify,supplier.orders,supplier.shippings,supplier.movements',
            'parent_id' => $admin_role->id
        ]);

        $gas->setConfig('roles', (object) [
            'user' => $user_role->id,
            'friend' => $user_role->id
        ]);

        $admin = User::create([
            'id' => str_slug('Amministratore Globale'),
            'gas_id' => $gas->id,
            'member_since' => date('Y-m-d', time()),
            'username' => 'root',
            'firstname' => 'Amministratore',
            'lastname' => 'Globale',
            'password' => Hash::make('root'),
        ]);

        $admin->addRole($user_role, $gas);
        $admin->addRole($admin_role, $gas);

        $categories = ['Non Specificato', 'Frutta', 'Verdura', 'Cosmesi', 'Bevande'];
        foreach ($categories as $cat) {
            Category::create([
                'id' => str_slug($cat),
                'name' => $cat,
            ]);
        }

        $measures = ['Non Specificato', 'Chili', 'Litri', 'Pezzi'];
        foreach ($measures as $name) {
            Measure::create([
                'id' => str_slug($name),
                'name' => $name,
            ]);
        }

        VatRate::create([
            'name' => 'Minima',
            'percentage' => 4,
        ]);

        VatRate::create([
            'name' => 'Ridotta',
            'percentage' => 10,
        ]);

        VatRate::create([
            'name' => 'Ordinaria',
            'percentage' => 22,
        ]);

        $notification = Notification::create([
            'creator_id' => $admin->id,
            'content' => "Benvenuto in GASdotto!\n\nClicca l'icona in alto a destra col punto interrogativo per attivare l'help in linea, ed ottenere una breve descrizione dei campi editabili.\nPer ulteriore assistenza puoi rivolgerti alla mailing list degli utenti su https://groups.google.com/forum/#!forum/gasdotto-dev",
            'mailed' => false,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 day')),
        ]);

        $notification->users()->attach($admin->id, ['done' => false]);

        $this->call(MovementTypesSeeder::class);
    }
}
