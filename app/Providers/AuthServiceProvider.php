<?php

namespace App\Providers;

use App\Client;
use App\Company;
use App\Invoice;
use App\Payment;
use App\Service;
use App\Policies\ClientPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ServicePolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Invoice::class => InvoicePolicy::class,
        Service::class => ServicePolicy::class,
        Company::class => CompanyPolicy::class,
        Client::class => ClientPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        
        Gate::define('super_admin.access', function($user) {
            return $user->getRole()->name != 'Super Admin';
        });

        Gate::define('super_admin.view', function($user) {
            return $user->getRole()->name == 'Super Admin';
        });

        Gate::define('view-companies', function($user) {
            return $user->getRole()->name == 'Super Admin';
        });
        
        
        Gate::define('general_user.access', function($user) {
            return $user->getRole()->name != 'General User';
        });

        Gate::define('general_user.view', function($user) {
            return $user->getRole()->name == 'General User';
        });

        //Company Admin
        Gate::define('company_admin.access', function($user) {
            return $user->getRole()->name != 'General User' || $user->getRole()->name != 'Super Admin';
        });

        Gate::define('company_admin.view', function($user) {
            return $user->getRole()->name == 'Company Admin';
        });

        Gate::define('users.delete', 'App\Policies\UserPolicy@delete');
        
        Gate::define('delete', function($user) {
            return $user->getRole()->name == 'General User';
        });


    }
}
