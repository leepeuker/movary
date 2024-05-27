<?php declare(strict_types=1);

namespace Movary\Service\Router\Dto;

class Route
{
    public function __construct(
        private readonly string $httpMethod,
        private readonly string $route,
        private readonly array $handler,
        private readonly array $middleware,
    ) {
    }

    public static function create(string $httpMethod, string $route, array $handler, array $middleware = []) : self
    {
        return new self($httpMethod, $route, $handler, $middleware);
    }

    public function getHandler() : array
    {
        return $this->handler;
    }

    public function getMethod() : string
    {
        return $this->httpMethod;
    }

    public function getMiddleware() : ?array
    {
        return $this->middleware;
    }

    public function getRoute() : string
    {
        return $this->route;
    }
}
