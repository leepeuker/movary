<?php

namespace Movary\Service\Router\Dto;

use Movary\Service\Router\Dto\Route;
use Movary\ValueObject\AbstractList;

class RouteList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function createNewRoute(string $httpMethod, string $route, array $handler) : Route
    {
        $route = Route::create($httpMethod, $route, $handler);
        $this->data[] = $route;
        return $route;
    }

    public function getRoutes() : array
    {
        return $this->data;
    }
}