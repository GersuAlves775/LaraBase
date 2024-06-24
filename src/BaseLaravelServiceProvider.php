<?php

namespace gersonalves\laravelBase;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;
use gersonalves\laravelBase\Helpers\CustomResourceRegistrar;

class BaseLaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        require_once __DIR__ . '/Helpers/Utility.php';

        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerateCommand::class,
            ]);
        }

        $this->app->bind('Illuminate\Routing\ResourceRegistrar', function ($app) {
            return new CustomResourceRegistrar($app['router']);
        });

        Blueprint::macro('userRegister', function () {
            $this->unsignedBigInteger('created_by')->nullable();
            $this->foreign('created_by')
                ->references('id_user')
                ->on('user');

            $this->unsignedBigInteger('updated_by')->nullable();
            $this->foreign('updated_by')
                ->references('id_user')
                ->on('user');
        });

        $this->publishes([
            __DIR__ . './config/larabase.php' => config_path('larabase.php'),
        ]);
    }

    public function register()
    {

    }
}
