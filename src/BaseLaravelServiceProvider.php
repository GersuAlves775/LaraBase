<?php

namespace gersonalves\laravelBase;

use Illuminate\Support\ServiceProvider;

class BaseLaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        require_once('Service/BaseService.php');
        require_once('Service/BaseServiceInterface.php');
    }

    public function register()
    {

    }
}
