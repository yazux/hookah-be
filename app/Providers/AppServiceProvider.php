<?php

namespace App\Providers;

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
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /*if ($this->app->environment() !== 'production') {
            $this->app->register(\Way\Generators\GeneratorsServiceProvider::class);
            $this->app->register(\Xethron\MigrationsGenerator\MigrationsGeneratorServiceProvider::class);
        }*/

        $this->app->bind(
            'pusher',
            function ($app, $parameters) {
                return new \Pusher\Pusher(
                    $parameters['app_key'],
                    $parameters['app_secret'],
                    $parameters['app_id'],
                    [
                        'cluster' => $parameters['options']['cluster'],
                        'encrypted' => true
                    ]
                );
            }
        );
    }
}
