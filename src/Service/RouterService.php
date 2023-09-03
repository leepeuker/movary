<?php

use FastRoute\RouteCollector;


class RouteDto extends RouteCollector
{
    public function __construct($httpMethod, $route, $handler) {
        $this->addRoute($httpMethod, $route, [[$handler]]);
    }

    public static function create($httpMethod, $route, $handler)
    {
        return new self($httpMethod, $route, $handler);
    }

    public function getMiddleware()
    {
        
    }
}


class RouterService
{
    public function createNewRoute($httpMethod, $route, $handler) : RouteDto
    {
        return RouteDto::create($httpMethod, $route, $handler);
    }
}