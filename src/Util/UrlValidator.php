<?php declare(strict_types=1);

namespace Movary\Util;

use Movary\ValueObject\Exception\InvalidSafeUrl;
use Movary\ValueObject\Url;
use Symfony\Component\HttpFoundation\IpUtils;

class UrlValidator
{
    private const array ALLOWED_PORTS = [80, 443, 8096, 8920, 32400];

    private const array ALLOWED_SCHEMES = ['http', 'https'];

    // Non-global ranges not covered by IpUtils::PRIVATE_SUBNETS.
    private const array NON_GLOBAL_IPV4_CIDRS = [
        '192.0.0.0/24',
        '192.88.99.0/24',
        '224.0.0.0/4',
    ];

    private const array NON_GLOBAL_IPV6_CIDRS = [
        '2001::/23',
        '3fff::/20',
    ];

    public function __construct(private readonly DnsResolver $dnsResolver)
    {
    }

    /** @param array<int, string> $allowlist */
    public function validateUrlIsSafe(Url $url, array $allowlist = []) : ValidatedUrl
    {
        $parsedUrl = parse_url((string)$url);

        if ($parsedUrl === false || isset($parsedUrl['host']) === false || isset($parsedUrl['scheme']) === false) {
            throw InvalidSafeUrl::create((string)$url);
        }

        $scheme = strtolower($parsedUrl['scheme']);
        if (in_array($scheme, self::ALLOWED_SCHEMES, true) === false) {
            throw new InvalidSafeUrl('URL scheme not allowed: ' . $scheme);
        }

        $host = $this->normalizeHost($parsedUrl['host']);
        $port = $parsedUrl['port'] ?? ($scheme === 'https' ? 443 : 80);
        if (in_array($port, self::ALLOWED_PORTS, true) === false) {
            throw new InvalidSafeUrl('Port not allowed: ' . $port);
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) === false
            ? $this->dnsResolver->resolve($host)
            : [$host];

        if ($addresses === []) {
            throw new InvalidSafeUrl('Could not resolve hostname: ' . $host);
        }

        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP) === false) {
                throw new InvalidSafeUrl('Hostname resolved to an invalid IP address: ' . $host);
            }

            if ($this->isPublicIp($address) === false && $this->isAllowed($host, $address, $allowlist) === false) {
                throw new InvalidSafeUrl('Destination is not allowed: ' . $host . ' -> ' . $address);
            }
        }

        return new ValidatedUrl($scheme, $host, $port, $addresses[0]);
    }

    /** @param array<int, string> $allowlist */
    private function isAllowed(string $host, string $ip, array $allowlist) : bool
    {
        foreach ($allowlist as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }

            if (str_contains($entry, '/') === true && $this->ipMatchesCidr($ip, $entry) === true) {
                return true;
            }

            $normalizedEntry = $this->normalizeHost($entry);
            if (filter_var($normalizedEntry, FILTER_VALIDATE_IP) !== false) {
                if (inet_pton($normalizedEntry) === inet_pton($ip)) {
                    return true;
                }

                continue;
            }

            if (strtolower($normalizedEntry) === strtolower($host)) {
                return true;
            }
        }

        return false;
    }

    private function isPublicIp(string $ip) : bool
    {
        if (IpUtils::isPrivateIp($ip) === true) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return IpUtils::checkIp($ip, self::NON_GLOBAL_IPV4_CIDRS) === false && filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) !== false;
        }

        return IpUtils::checkIp($ip, '2000::/3')
            && IpUtils::checkIp($ip, self::NON_GLOBAL_IPV6_CIDRS) === false;
    }

    private function ipMatchesCidr(string $ip, string $cidr) : bool
    {
        return IpUtils::checkIp($ip, $cidr);
    }

    private function normalizeHost(string $host) : string
    {
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            return substr($host, 1, -1);
        }

        return $host;
    }
}
