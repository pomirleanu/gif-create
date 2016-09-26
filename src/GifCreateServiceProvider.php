<?php

namespace pomirleanu\GifCreate;

use Illuminate\Support\ServiceProvider;

class GifCreateServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(array(
            __DIR__.'/../../config/config.php' => config_path('gif.php')
        ));
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $app = $this->app;
        // merge default config
        $this->mergeConfigFrom(
            __DIR__.'../config/config.php',
            'gif'
        );
        // create image
        $app['gif'] = $app->share(function ($app) {
            return new GifCreate($app['config']->get('gif'));
        });
        $app->alias('gif', 'Pomirleanu\GifCreate');
    }
}