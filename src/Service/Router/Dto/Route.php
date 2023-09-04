<?php

namespace Movary\Service\Router\Dto;

class Route
{
    private array $handler;
    private array $middleware = [];
    private string $httpMethod;
    private string $route;
    
    public function __construct($httpMethod, $route, array $handler) {
        $this->handler = $handler;
        $this->httpMethod = $httpMethod;
        $this->route = $route;
    }

    public static function create(string $httpMethod, string $route, array $handler)
    {
        return new self($httpMethod, $route, $handler);
    }

    public function getHandler() : array
    {
        return $this->handler;
    }

    public function getMethod() : string
    {
        return $this->httpMethod;
    }

    public function getRoute() : string
    {
        return $this->route;
    }

    public function getMiddleware() :?array
    {
        return $this->middleware;
    }

    public function addMiddleware(...$middlewares) : void
    {
        $this->middleware = $middlewares;
    }
}