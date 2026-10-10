<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Router;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Movary\HttpController\Api\Middleware\IsAuthenticated;
use Movary\HttpController\Api\Middleware\IsAuthorizedToWriteUserData;
use Movary\HttpController\Web\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class RouteSecurityTest extends TestCase
{
    private const array UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** @var null|list<array{method: string, path: string, middleware: array<string>}> */
    private static ?array $routes = null;

    public function testUnsafeWebRoutesAreProtectedExceptForDeprecatedWebhooks() : void
    {
        $routes = array_filter(
            $this->collectRoutes(),
            static fn(array $route) : bool => str_starts_with($route['path'], '/api/') === false
                && in_array($route['method'], self::UNSAFE_METHODS, true),
        );

        $exemptRoutes = [];
        foreach ($routes as $route) {
            if (in_array(ValidateCsrfToken::class, $route['middleware'], true) === false) {
                $exemptRoutes[] = $route['method'] . ' ' . $route['path'];
            }
        }

        sort($exemptRoutes);
        self::assertSame(
            [
                'POST /emby/{id:.+}',
                'POST /jellyfin/{id:.+}',
                'POST /plex/{id:.+}',
            ],
            $exemptRoutes,
        );
    }

    public function testSafeWebRoutesDoNotReceiveCsrfMiddleware() : void
    {
        $routes = array_filter(
            $this->collectRoutes(),
            static fn(array $route) : bool => str_starts_with($route['path'], '/api/') === false
                && in_array($route['method'], self::UNSAFE_METHODS, true) === false,
        );

        foreach ($routes as $route) {
            self::assertNotContains(ValidateCsrfToken::class, $route['middleware']);
        }
    }

    public function testUnsafeApiRoutesWithoutAuthenticationMiddlewareAreLimitedToExplicitBoundaries() : void
    {
        $routes = array_filter(
            $this->collectRoutes(),
            static fn(array $route) : bool => str_starts_with($route['path'], '/api/') === true
                && in_array($route['method'], self::UNSAFE_METHODS, true),
        );

        $explicitBoundaries = [];
        foreach ($routes as $route) {
            $usesApiAuthentication = in_array(IsAuthenticated::class, $route['middleware'], true)
                || in_array(IsAuthorizedToWriteUserData::class, $route['middleware'], true);
            if ($usesApiAuthentication === false) {
                $explicitBoundaries[] = $route['method'] . ' ' . $route['path'];
            }
        }

        sort($explicitBoundaries);
        self::assertSame(
            [
                'POST /api/webhook/emby/{id:.+}',
                'POST /api/webhook/jellyfin/{id:.+}',
                'POST /api/webhook/kodi/{id:.+}',
                'POST /api/webhook/plex/{id:.+}',
            ],
            $explicitBoundaries,
        );
    }

    public function testApiAuthenticationTokenRouteOnlySupportsIdentityCheck() : void
    {
        $methods = [];
        foreach ($this->collectRoutes() as $route) {
            if ($route['path'] === '/api/authentication/token') {
                $methods[] = $route['method'];
            }
        }

        self::assertSame(['GET'], $methods);
    }

    /** @return list<array{method: string, path: string, middleware: array<string>}> */
    private function collectRoutes() : array
    {
        if (self::$routes !== null) {
            return self::$routes;
        }

        $collector = new class extends RouteCollector {
            /** @var list<array{method: string, path: string, middleware: array<string>}> */
            public array $routes = [];

            public function __construct()
            {
                parent::__construct(new Std(), new GroupCountBased());
            }

            /**
             * @param string|string[] $httpMethod
             * @param string $route
             * @param mixed $handler
             */
            public function addRoute($httpMethod, $route, $handler) : void
            {
                if (is_array($handler) === false || isset($handler['middleware']) === false) {
                    throw new \UnexpectedValueException('Route handler does not contain middleware.');
                }

                /** @var array<string> $middleware */
                $middleware = $handler['middleware'];
                foreach ((array)$httpMethod as $method) {
                    $this->routes[] = [
                        'method' => $method,
                        'path' => $this->currentGroupPrefix . $route,
                        'middleware' => $middleware,
                    ];
                }
            }
        };

        $registerRoutes = require dirname(__DIR__, 4) . '/settings/routes.php';
        $registerRoutes($collector);

        self::$routes = $collector->routes;

        return self::$routes;
    }
}
