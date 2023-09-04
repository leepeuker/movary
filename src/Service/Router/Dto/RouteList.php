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

    public function addRoute(Route $route) : Route
    {
        $this->data[] = $route;
        return end($this->data);
    }

    public function addRoutes(...$routes) : void
    {
        $this->data = array_merge($this->data, $routes);
    }

    public function getRoutes() : array
    {
        return $this->data;
    }
}