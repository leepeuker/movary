<?php declare(strict_types=1);

namespace Tests\Api\Movary\Support;

use DI\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class ApiTestCase extends TestCase
{
    protected ApiClient $api;

    protected ApiFixture $fixture;

    protected function setUp() : void
    {
        $baseUrl = getenv('API_BASE_URL');
        if ($baseUrl === false || $baseUrl === '') {
            throw new RuntimeException('API_BASE_URL must be set when running the API test suite.');
        }

        /** @var Container $container */
        $container = require dirname(__DIR__, 3) . '/bootstrap.php';

        $this->api = new ApiClient($baseUrl);
        $this->fixture = new ApiFixture($container);
    }

    /** @return array{Authorization: string} */
    protected function createAuthorizationHeader(string $token) : array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }
}
