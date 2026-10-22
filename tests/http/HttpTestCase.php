<?php declare(strict_types=1);

namespace Tests\Http\Movary;

use DI\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class HttpTestCase extends TestCase
{
    protected HttpClient $http;

    protected HttpFixture $fixture;

    protected function setUp() : void
    {
        $baseUrl = getenv('HTTP_BASE_URL');
        if ($baseUrl === false || $baseUrl === '') {
            throw new RuntimeException('HTTP_BASE_URL must be set when running the HTTP test suite.');
        }

        /** @var Container $container */
        $container = require dirname(__DIR__, 2) . '/bootstrap.php';

        $this->http = new HttpClient($baseUrl);
        $this->fixture = new HttpFixture($container);
    }

    /** @return array{Authorization: string} */
    protected function createAuthorizationHeader(string $token) : array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }
}
