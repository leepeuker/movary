<?php declare(strict_types=1);

namespace Movary\Service\Router;

use FastRoute\RouteCollector;
use Movary\Service\Router\Dto\RouteList;

class RouterService
{
    public function addRoutesToRouteCollector(RouteCollector $routeCollector, RouteList $routeList) : void
    {
        foreach ($routeList as $route) {
            $routeCollector->addRoute(
                $route->getMethod(),
                $route->getRoute(),
                [
                    'handler' => $route->getHandler(),
                    'middleware' => $route->getMiddleware()
                ],
            );
        }
    }
}
