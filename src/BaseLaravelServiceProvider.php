<?php

namespace gersonalves\laravelBase;

use Illuminate\Support\ServiceProvider;

class BaseLaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        require_once('Service/BaseServiceInterface.php');
        require_once('Repository/BaseRepositoryInterface.php');
        require_once('Service/BaseService.php');
        require_once('Repository/BaseRepository.php');
    }

    public function register()
    {

    }
}
