<?php declare(strict_types=1);

namespace Movary\Service\Router\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<Route>
 */
class RouteList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function add(string $httpMethod, string $route, array $handler, array $middleware = []) : Route
    {
        $route = Route::create($httpMethod, $route, $handler, $middleware);
        $this->data[] = $route;

        return $route;
    }
}
