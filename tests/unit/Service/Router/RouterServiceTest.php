<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Router;

use FastRoute\RouteCollector;
use Movary\HttpController\Web\Middleware\ValidateCsrfToken;
use Movary\Service\Router\Dto\RouteList;
use Movary\Service\Router\RouterService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\Router\RouterService::class)]
class RouterServiceTest extends TestCase
{
    private const array HANDLER = ['Controller', 'method'];

    public function testUnsafeWebRouteGetsCsrfMiddleware() : void
    {
        $routeList = RouteList::create();
        $routeList->add('POST', '/test', self::HANDLER, ['ExistingMiddleware']);

        $collector = $this->createMock(RouteCollector::class);
        $collector
            ->expects(self::once())
            ->method('addRoute')
            ->with(
                'POST',
                '/test',
                [
                    'handler' => self::HANDLER,
                    'middleware' => ['ExistingMiddleware', ValidateCsrfToken::class],
                ],
            );

        (new RouterService())->addRoutesToRouteCollector($collector, $routeList, true);
    }

    public function testSafeWebRouteDoesNotGetCsrfMiddleware() : void
    {
        $routeList = RouteList::create();
        $routeList->add('GET', '/test', self::HANDLER);

        $collector = $this->createMock(RouteCollector::class);
        $collector
            ->expects(self::once())
            ->method('addRoute')
            ->with(
                'GET',
                '/test',
                ['handler' => self::HANDLER, 'middleware' => []],
            );

        (new RouterService())->addRoutesToRouteCollector($collector, $routeList, true);
    }

    public function testExplicitlyExcludedUnsafeWebRouteDoesNotGetCsrfMiddleware() : void
    {
        $routeList = RouteList::create();
        $routeList->add('POST', '/webhook', self::HANDLER, csrfProtectionEnabled: false);

        $collector = $this->createMock(RouteCollector::class);
        $collector
            ->expects(self::once())
            ->method('addRoute')
            ->with(
                'POST',
                '/webhook',
                ['handler' => self::HANDLER, 'middleware' => []],
            );

        (new RouterService())->addRoutesToRouteCollector($collector, $routeList, true);
    }

    public function testApiRouteDoesNotGetWebMiddleware() : void
    {
        $routeList = RouteList::create();
        $routeList->add('POST', '/test', self::HANDLER, ['ApiMiddleware']);

        $collector = $this->createMock(RouteCollector::class);
        $collector
            ->expects(self::once())
            ->method('addRoute')
            ->with(
                'POST',
                '/test',
                ['handler' => self::HANDLER, 'middleware' => ['ApiMiddleware']],
            );

        (new RouterService())->addRoutesToRouteCollector($collector, $routeList);
    }
}
