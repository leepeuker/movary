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
            'loopback IPv6' => ['http://[::1]'],
            'IPv4-mapped private address' => ['http://[::ffff:10.0.0.1]'],
            'NAT64 private address' => ['http://[64:ff9b::a9fe:a9fe]'],
            '6to4 private address' => ['http://[2002:0a00:0001::]'],
        ];
    }
}
