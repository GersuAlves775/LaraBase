<?php

namespace gersonalves\laravelBase\Helpers;

use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Facades\Route;

class CustomResourceRegistrar extends ResourceRegistrar
{
    protected function addResourceSearch($name, $controller, $options)
    {
        $uri = $this->getResourceUri($name) . '/search';

        $action = $this->getResourceAction($name, $controller, 'search', $options);

        return $this->router->get($uri, $action);
    }

    public function register($name, $controller, array $options = [])
    {
        if (method_exists($controller, 'search')) {
            $this->addResourceSearch($name, $controller, $options);
        }

        parent::register($name, $controller, $options);
    }
}
