<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Util;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Movary\Util\DnsResolver;
use Movary\Util\SafeHttpClient;
use Movary\Util\UrlValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

#[CoversClass(\Movary\Util\SafeHttpClient::class)]
class SafeHttpClientTest extends TestCase
{
    public function testStreamHandlerConnectsToValidatedIpAndPreservesOriginalHost() : void
    {
        $dnsResolver = $this->createStub(DnsResolver::class);
        $dnsResolver->method('resolve')->willReturn(['10.0.0.8']);
        /** @var array<int, array{request: RequestInterface, response: ?ResponseInterface, error: ?Throwable, options: array<string, mixed>}> $history */
        $history = [];
        $handler = HandlerStack::create(new MockHandler([new Response()]));
        $handler->push(Middleware::history($history));
        $subject = new SafeHttpClient(
            new UrlValidator($dnsResolver),
            ['10.0.0.0/24'],
            ['handler' => $handler],
            false,
        );

        $subject->request('GET', 'https://jellyfin.example:8920/system/info');

        /** @var array{request: RequestInterface, response: ?ResponseInterface, error: ?Throwable, options: array<string, mixed>} $transaction */
        $transaction = $history[0];
        $options = $transaction['options'];
        /** @var array{ssl: array{peer_name: string}} $streamContext */
        $streamContext = $options['stream_context'];

        self::assertSame('10.0.0.8', $transaction['request']->getUri()->getHost());
        self::assertSame('jellyfin.example:8920', $transaction['request']->getHeaderLine('Host'));
        self::assertFalse($options['allow_redirects']);
        self::assertSame('', $options['proxy']);
        self::assertSame(30, $options['timeout']);
        self::assertSame('jellyfin.example', $streamContext['ssl']['peer_name']);
    }

    public function testCurlHandlerPinsValidatedIp() : void
    {
        if (defined('CURLOPT_RESOLVE') === false) {
            self::markTestSkipped('PHP cURL is not installed');
        }

        $dnsResolver = $this->createStub(DnsResolver::class);
        $dnsResolver->method('resolve')->willReturn(['10.0.0.8']);
        /** @var array<int, array{request: RequestInterface, response: ?ResponseInterface, error: ?Throwable, options: array<string, mixed>}> $history */
        $history = [];
        $handler = HandlerStack::create(new MockHandler([new Response()]));
        $handler->push(Middleware::history($history));
        $subject = new SafeHttpClient(
            new UrlValidator($dnsResolver),
            ['jellyfin.example'],
            ['handler' => $handler],
            true,
        );

        $subject->request('GET', 'http://jellyfin.example:8096/system/info');

        /** @var array{request: RequestInterface, response: ?ResponseInterface, error: ?Throwable, options: array<string, mixed>} $transaction */
        $transaction = $history[0];
        $options = $transaction['options'];
        /** @var array<int, array<int, string>> $curlOptions */
        $curlOptions = $options['curl'];

        self::assertSame(
            ['jellyfin.example:8096:10.0.0.8'],
            $curlOptions[constant('CURLOPT_RESOLVE')],
        );
        self::assertSame('jellyfin.example', $transaction['request']->getUri()->getHost());
        self::assertFalse($options['allow_redirects']);
    }
}
