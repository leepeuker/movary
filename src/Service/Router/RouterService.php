<?php

namespace Movary\Service\Router;

use FastRoute\RouteCollector;
use Movary\Service\Router\Dto\Route;
use Movary\Service\Router\Dto\RouteList;

class RouterService
{
    public function createNewRoute(string $httpMethod, string $route, array $handler) : Route
    {
        return Route::create($httpMethod, $route, $handler);
    }


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