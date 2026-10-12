<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Util;

use Movary\Util\DnsResolver;
use Movary\Util\UrlValidator;
use Movary\ValueObject\Exception\InvalidSafeUrl;
use Movary\ValueObject\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Util\UrlValidator::class)]
class UrlValidatorTest extends TestCase
{
    public function testAcceptsPublicAAndAaaaRecords() : void
    {
        $dnsResolver = $this->createMock(DnsResolver::class);
        $dnsResolver->expects(self::once())->method('resolve')->with('media.example.com')->willReturn([
            '203.0.113.10',
            '2001:4860:4860::8888',
        ]);

        $validatedUrl = (new UrlValidator($dnsResolver))->validateUrlIsSafe(
            Url::createFromString('https://media.example.com'),
            ['203.0.113.0/24'],
        );

        self::assertSame('media.example.com:443:203.0.113.10', $validatedUrl->createCurlResolveEntry());
    }

    public function testRejectsHostnameIfAnyAddressIsPrivate() : void
    {
        $dnsResolver = $this->createStub(DnsResolver::class);
        $dnsResolver->method('resolve')->willReturn(['8.8.8.8', '10.0.0.1']);

        $this->expectException(InvalidSafeUrl::class);
        $this->expectExceptionMessage('Destination is not allowed');

        (new UrlValidator($dnsResolver))->validateUrlIsSafe(Url::createFromString('https://media.example.com'));
    }

    public function testRejectsHostnameIfAaaaRecordIsPrivate() : void
    {
        $dnsResolver = $this->createStub(DnsResolver::class);
        $dnsResolver->method('resolve')->willReturn(['8.8.8.8', 'fd00::1']);

        $this->expectException(InvalidSafeUrl::class);
        $this->expectExceptionMessage('Destination is not allowed');

        (new UrlValidator($dnsResolver))->validateUrlIsSafe(Url::createFromString('https://media.example.com'));
    }

    public function testAllowsPrivateAddressForExactHostname() : void
    {
        $dnsResolver = $this->createStub(DnsResolver::class);
        $dnsResolver->method('resolve')->willReturn(['10.0.0.8']);

        $validatedUrl = (new UrlValidator($dnsResolver))->validateUrlIsSafe(
            Url::createFromString('http://jellyfin.local:8096'),
            ['jellyfin.local'],
        );

        self::assertSame('jellyfin.local:8096:10.0.0.8', $validatedUrl->createCurlResolveEntry());
    }

    public function testAllowsPrivateIpv6AddressInConfiguredCidr() : void
    {
        $validatedUrl = (new UrlValidator($this->createStub(DnsResolver::class)))->validateUrlIsSafe(
            Url::createFromString('https://[fd00::12]'),
            ['fd00::/64'],
        );

        self::assertSame('[fd00::12]', parse_url($validatedUrl->createPinnedUrl(
            Url::createFromString('https://[fd00::12]'),
        ), PHP_URL_HOST));
    }

    public function testAllowsStandardPlexPort() : void
    {
        $validatedUrl = (new UrlValidator($this->createStub(DnsResolver::class)))->validateUrlIsSafe(
            Url::createFromString('http://192.168.1.10:32400'),
            ['192.168.1.10'],
        );

        self::assertSame('192.168.1.10:32400:192.168.1.10', $validatedUrl->createCurlResolveEntry());
    }

    public function testAllowsReservedAddressInConfiguredCidr() : void
    {
        $validatedUrl = (new UrlValidator($this->createStub(DnsResolver::class)))->validateUrlIsSafe(
            Url::createFromString('http://198.18.0.10'),
            ['198.18.0.0/15'],
        );

        self::assertSame('198.18.0.10:80:198.18.0.10', $validatedUrl->createCurlResolveEntry());
    }

    #[DataProvider('provideUnsafeUrls')]
    public function testRejectsUnsafeUrl(string $url, string $expectedUserMessage) : void
    {
        try {
            (new UrlValidator($this->createStub(DnsResolver::class)))->validateUrlIsSafe(Url::createFromString($url));
            self::fail('Expected unsafe URL to be rejected');
        } catch (InvalidSafeUrl $e) {
            self::assertSame($expectedUserMessage, $e->getUserMessage());
        }
    }

    public function testExplainsHostnameResolutionFailure() : void
    {
        $dnsResolver = $this->createStub(DnsResolver::class);
        $dnsResolver->method('resolve')->willReturn([]);

        try {
            (new UrlValidator($dnsResolver))->validateUrlIsSafe(Url::createFromString('https://unknown.example'));
            self::fail('Expected unresolved hostname to be rejected');
        } catch (InvalidSafeUrl $e) {
            self::assertSame(
                'Server hostname could not be resolved. Check the hostname and DNS configuration.',
                $e->getUserMessage(),
            );
        }
    }

    public static function provideUnsafeUrls() : array
    {
        return [
            'unsupported scheme' => ['ftp://8.8.8.8', 'Server URL must use http:// or https://.'],
            'unsupported port' => [
                'https://8.8.8.8:22',
                'Server URL uses an unsupported port. Allowed ports: 80, 443, 8096, 8920, and 32400.',
            ],
            'private IPv4' => ['http://10.0.0.1', self::disallowedDestinationMessage()],
            'shared IPv4' => ['http://100.64.0.1', self::disallowedDestinationMessage()],
            'protocol assignment IPv4' => ['http://192.0.0.1', self::disallowedDestinationMessage()],
            'documentation IPv4' => ['http://192.0.2.1', self::disallowedDestinationMessage()],
            'deprecated 6to4 relay IPv4' => ['http://192.88.99.1', self::disallowedDestinationMessage()],
            'benchmarking IPv4' => ['http://198.18.0.1', self::disallowedDestinationMessage()],
            'multicast IPv4' => ['http://224.0.0.1', self::disallowedDestinationMessage()],
            'loopback IPv6' => ['http://[::1]', self::disallowedDestinationMessage()],
            'documentation IPv6' => ['http://[2001:db8::1]', self::disallowedDestinationMessage()],
            'segment routing IPv6' => ['http://[5f00::1]', self::disallowedDestinationMessage()],
            'multicast IPv6' => ['http://[ff02::1]', self::disallowedDestinationMessage()],
            'IPv4-mapped private address' => ['http://[::ffff:10.0.0.1]', self::disallowedDestinationMessage()],
            'NAT64 transition address' => ['http://[64:ff9b::808:808]', self::disallowedDestinationMessage()],
            'NAT64 private address' => ['http://[64:ff9b::a9fe:a9fe]', self::disallowedDestinationMessage()],
            'NAT64 shared address' => ['http://[64:ff9b::6440:1]', self::disallowedDestinationMessage()],
            '6to4 private address' => ['http://[2002:0a00:0001::]', self::disallowedDestinationMessage()],
        ];
    }

    private static function disallowedDestinationMessage() : string
    {
        return 'Server URL resolves to a private or reserved IP address.';
    }
}
