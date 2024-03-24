<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use App\Extensions\BypassUserProvider;

use Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $all_permissions = allPermissions();
        foreach ($all_permissions as $rules) {
            foreach (array_keys($rules) as $identifier) {
                Gate::define($identifier, function ($user, $obj = null) use ($identifier) {
                    foreach($user->roles as $role) {
                        if ($role->enabledAction($identifier)) {
                            if(is_null($obj) || $role->applies($obj)) {
                                return true;
                            }
                        }
                    }

                    return false;
                });
            }
        }

        Auth::provider('bypass', function ($app, array $config) {
            return new BypassUserProvider($app['hash'], $config['model']);
        });
    }
}
