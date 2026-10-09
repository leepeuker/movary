<?php declare(strict_types=1);

namespace Movary\Service\Router\Dto;

class Route
{
    public function __construct(
        private readonly string $httpMethod,
        private readonly string $route,
        private readonly array $handler,
        private readonly array $middleware,
        private readonly bool $csrfProtectionEnabled,
    ) {
    }

    public static function create(
        string $httpMethod,
        string $route,
        array $handler,
        array $middleware = [],
        bool $csrfProtectionEnabled = true,
    ) : self {
        return new self($httpMethod, $route, $handler, $middleware, $csrfProtectionEnabled);
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

    public function isCsrfProtectionEnabled() : bool
    {
        return $this->csrfProtectionEnabled;
    }
}
