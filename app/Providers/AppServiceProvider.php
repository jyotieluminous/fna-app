<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Http\View\Composers\RoleComposer;
use App\Http\View\Composers\UserComposer;
use App\Http\View\Composers\ClientComposer;
use App\Http\View\Composers\ServiceComposer;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
       
        view()->composer(['services.index'], ServiceComposer::class);
        view()->composer(['clients.index', 'invoices.create'], ClientComposer::class);
        view()->composer(['user.index', 'user.store'], UserComposer::class);
        view()->composer(['user.create', 'user.edit'], RoleComposer::class);
    }
}
