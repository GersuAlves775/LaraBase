<?php

namespace gersonalves\laravelBase;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;


class BaseLaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        require_once(__DIR__ . '/Helpers/Utility.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerateCommand::class
            ]);
        }

        Blueprint::macro('userRegister', function () {
            $this->unsignedBigInteger('created_by');
            $this->foreign('created_by')
                ->references('id_user')
                ->on('user');

            $this->unsignedBigInteger('updated_by');
            $this->foreign('updated_by')
                ->references('id_user')
                ->on('user');
        });
    }

    public function register()
    {

    }

}
