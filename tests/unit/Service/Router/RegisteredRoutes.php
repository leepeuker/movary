<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Router;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;

class RegisteredRoutes
{
    /** @var null|list<array{method: string, path: string, middleware: array<string>}> */
    private static ?array $routes = null;

    /** @return list<array{method: string, path: string, middleware: array<string>}> */
    public static function collect() : array
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
