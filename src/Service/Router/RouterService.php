<?php declare(strict_types=1);

namespace Movary\Service\Router;

use FastRoute\RouteCollector;
use Movary\HttpController\Web;
use Movary\Service\Router\Dto\RouteList;

class RouterService
{
    public function addRoutesToRouteCollector(RouteCollector $routeCollector, RouteList $routeList, bool $isWebRoute = false) : void
    {
        foreach ($routeList as $route) {
            $middleware = $route->getMiddleware();
            if ($isWebRoute === true) {
                $middleware[] = Web\Middleware\StartSession::class;
            }

            $routeCollector->addRoute(
                $route->getMethod(),
                $route->getRoute(),
                [
                    'handler' => $route->getHandler(),
                    'middleware' => $middleware
                ],
            );
        }
    }
}
