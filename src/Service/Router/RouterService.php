<?php

namespace Movary\Service\Router;

use FastRoute\RouteCollector;
use Movary\Service\Router\Dto\Route;
use Movary\Service\Router\Dto\RouteList;

class RouterService
{
    public function createRouteList() : RouteList
    {
        return RouteList::create();
    }
    
    public function generateRouteCallback(RouteCollector $routeCollector, RouteList $routeList) : void
    {
        foreach($routeList->getRoutes() as $route) {
            $routeCollector->addRoute(
                $route->getMethod(),
                $route->getRoute(),
                [
                    'handler' => $route->getHandler(),
                    'middleware' => $route->getMiddleware()
                ]
            );
        }
    }
}