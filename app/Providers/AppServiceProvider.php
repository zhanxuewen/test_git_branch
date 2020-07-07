<?php

namespace App\Providers;

use Tests\Query\Listener;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.env') == 'testing') {
            app()->singleton('Tests\Query\Listener', function ($app) {
                return new Listener();
            });
            \DB::listen(function ($query) {
                app()->make('Tests\Query\Listener')->analyzeSQL($query->sql, $query->bindings, $query->time);
            });
        }

        if(env('IS_HTTPS')){
            \URL::forceScheme('https');
        }


        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
