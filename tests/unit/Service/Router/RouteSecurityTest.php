<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Router;

use Movary\HttpController\Api\Middleware\IsAuthenticated;
use Movary\HttpController\Api\Middleware\IsAuthorizedToWriteUserData;
use Movary\HttpController\Web\Middleware\UserIsAdmin;
use Movary\HttpController\Web\Middleware\UserIsAuthenticated;
use Movary\HttpController\Web\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class RouteSecurityTest extends TestCase
{
    private const array UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function testUnsafeWebRoutesAreProtectedExceptForDeprecatedWebhooks() : void
    {
        $routes = array_filter(
            RegisteredRoutes::collect(),
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
                'POST /webhooks/emby/{token:.+}',
                'POST /webhooks/jellyfin/{token:.+}',
                'POST /webhooks/kodi/{token:.+}',
                'POST /webhooks/plex/{token:.+}',
            ],
            $exemptRoutes,
        );
    }

    public function testSafeWebRoutesDoNotReceiveCsrfMiddleware() : void
    {
        $routes = array_filter(
            RegisteredRoutes::collect(),
            static fn(array $route) : bool => str_starts_with($route['path'], '/api/') === false
                && in_array($route['method'], self::UNSAFE_METHODS, true) === false,
        );

        foreach ($routes as $route) {
            self::assertNotContains(ValidateCsrfToken::class, $route['middleware']);
        }
    }

    public function testTmdbJobRoutesRequireAdministratorAuthentication() : void
    {
        $routes = array_filter(
            RegisteredRoutes::collect(),
            static fn(array $route) : bool => in_array($route['path'], [
                '/jobs/schedule/tmdb-movie-sync',
                '/jobs/schedule/tmdb-person-sync',
                '/jobs/schedule/tmdb-image-cache-refresh',
                '/jobs/schedule/imdb-ratings-sync',
                '/job-schedules',
                '/job-schedules/{scheduleId:\d+}',
            ], true),
        );

        self::assertCount(7, $routes);
        foreach ($routes as $route) {
            self::assertContains(UserIsAuthenticated::class, $route['middleware']);
            self::assertContains(UserIsAdmin::class, $route['middleware']);
            self::assertContains(ValidateCsrfToken::class, $route['middleware']);
        }
    }

    public function testJobSettingsPagesRequireAdministratorAuthentication() : void
    {
        $routes = array_filter(
            RegisteredRoutes::collect(),
            static fn(array $route) : bool => in_array($route['path'], [
                '/settings/server/job-queue',
                '/settings/server/job-schedules',
            ], true),
        );

        self::assertCount(2, $routes);
        foreach ($routes as $route) {
            self::assertSame('GET', $route['method']);
            self::assertContains(UserIsAuthenticated::class, $route['middleware']);
            self::assertContains(UserIsAdmin::class, $route['middleware']);
        }
    }

    public function testJobScheduleFormUpdateUsesPost() : void
    {
        $methods = [];
        foreach (RegisteredRoutes::collect() as $route) {
            if ($route['path'] === '/job-schedules/{scheduleId:\d+}') {
                $methods[] = $route['method'];
            }
        }

        self::assertSame(['DELETE', 'POST'], $methods);
    }

    public function testUnsafeApiRoutesWithoutAuthenticationMiddlewareAreLimitedToExplicitBoundaries() : void
    {
        $routes = array_filter(
            RegisteredRoutes::collect(),
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
                'POST /api/webhook/emby/{token:.+}',
                'POST /api/webhook/jellyfin/{token:.+}',
                'POST /api/webhook/kodi/{token:.+}',
                'POST /api/webhook/plex/{token:.+}',
            ],
            $explicitBoundaries,
        );
    }

    public function testApiAuthenticationTokenRouteOnlySupportsIdentityCheck() : void
    {
        $methods = [];
        foreach (RegisteredRoutes::collect() as $route) {
            if (in_array($route['path'], ['/api/authentication/token', '/api/v1/authentication/token'], true)) {
                $methods[] = $route['method'];
            }
        }

        self::assertSame(['GET', 'GET'], $methods);
    }
}
