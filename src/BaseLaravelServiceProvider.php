<?php

namespace gersonalves\laravelBase;

use Illuminate\Support\ServiceProvider;

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
    }

    public function register()
    {

    }

}
