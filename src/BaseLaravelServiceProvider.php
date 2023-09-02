<?php

namespace gersonalves\laravelBase;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class BaseLaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        require_once __DIR__.'/Helpers/Utility.php';

        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerateCommand::class,
            ]);
        }

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
    }

    public function register()
    {

    }
}
