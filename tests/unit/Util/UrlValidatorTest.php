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
    public function testRejectsUnsafeUrl(string $url) : void
    {
        $this->expectException(InvalidSafeUrl::class);

        (new UrlValidator($this->createStub(DnsResolver::class)))->validateUrlIsSafe(Url::createFromString($url));
    }

    public static function provideUnsafeUrls() : array
    {
        return [
            'unsupported scheme' => ['ftp://8.8.8.8'],
            'unsupported port' => ['https://8.8.8.8:22'],
            'private IPv4' => ['http://10.0.0.1'],
            'shared IPv4' => ['http://100.64.0.1'],
            'protocol assignment IPv4' => ['http://192.0.0.1'],
            'documentation IPv4' => ['http://192.0.2.1'],
            'deprecated 6to4 relay IPv4' => ['http://192.88.99.1'],
            'benchmarking IPv4' => ['http://198.18.0.1'],
            'multicast IPv4' => ['http://224.0.0.1'],
            'loopback IPv6' => ['http://[::1]'],
            'documentation IPv6' => ['http://[2001:db8::1]'],
            'segment routing IPv6' => ['http://[5f00::1]'],
            'multicast IPv6' => ['http://[ff02::1]'],
            'IPv4-mapped private address' => ['http://[::ffff:10.0.0.1]'],
            'NAT64 transition address' => ['http://[64:ff9b::808:808]'],
            'NAT64 private address' => ['http://[64:ff9b::a9fe:a9fe]'],
            'NAT64 shared address' => ['http://[64:ff9b::6440:1]'],
            '6to4 private address' => ['http://[2002:0a00:0001::]'],
        ];
    }
}
