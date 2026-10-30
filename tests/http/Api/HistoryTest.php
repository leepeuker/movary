<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class HistoryTest extends HttpTestCase
{
    #[DataProvider('removedRouteProvider')]
    public function testGroupedHistoryAndOldPlayPathsAreUnavailable(string $path, string $method) : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $url = str_replace('{username}', $fixture['user']->getName(), $path);

        $this->http->request($method, $url, $this->createAuthorizationHeader($fixture['token']))->assertStatus(404);
    }

    public static function removedRouteProvider() : array
    {
        $cases = [];
        foreach (['/api/v1', '/api'] as $prefix) {
            foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
                $cases[$prefix . ' history ' . $method] = [$prefix . '/users/{username}/history/movies', $method];
            }
            $cases[$prefix . ' old plays GET'] = [$prefix . '/users/{username}/history/plays', 'GET'];
        }

        return $cases;
    }
}
