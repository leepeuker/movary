<?php declare(strict_types=1);

namespace Movary\Util;

use Movary\ValueObject\Exception\InvalidSafeUrl;
use Movary\ValueObject\Url;

class UrlValidator
{
    private const array ALLOWED_PORTS = [80, 443, 8096, 8920, 32400];

    private const array ALLOWED_SCHEMES = ['http', 'https'];

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
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        foreach ($this->extractEmbeddedIpv4Addresses($ip) as $embeddedIp) {
            if (filter_var($embeddedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, string> */
    private function extractEmbeddedIpv4Addresses(string $ip) : array
    {
        $packed = inet_pton($ip);
        if ($packed === false || strlen($packed) !== 16) {
            return [];
        }

        $hex = bin2hex($packed);

        if (str_starts_with($hex, '00000000000000000000ffff') === true
            || str_starts_with($hex, '0064ff9b0000000000000000') === true
        ) {
            return [$this->unpackIpv4(substr($packed, 12, 4))];
        }

        if (str_starts_with($hex, '2002') === true) {
            return [$this->unpackIpv4(substr($packed, 2, 4))];
        }

        if (str_starts_with($hex, '20010000') === true) {
            return [
                $this->unpackIpv4(substr($packed, 4, 4)),
                $this->unpackIpv4(substr($packed, 12, 4) ^ "\xff\xff\xff\xff"),
            ];
        }

        if (substr($hex, 16, 8) === '00005efe' || substr($hex, 16, 8) === '02005efe') {
            return [$this->unpackIpv4(substr($packed, 12, 4))];
        }

        return [];
    }

    private function ipMatchesCidr(string $ip, string $cidr) : bool
    {
        $cidrParts = explode('/', $cidr, 2);
        if (count($cidrParts) !== 2 || ctype_digit($cidrParts[1]) === false) {
            return false;
        }
        [$network, $prefixLength] = $cidrParts;

        $packedIp = inet_pton($ip);
        $packedNetwork = inet_pton($this->normalizeHost($network));
        if ($packedIp === false || $packedNetwork === false || strlen($packedIp) !== strlen($packedNetwork)) {
            return false;
        }

        $prefixLength = (int)$prefixLength;
        $maximumPrefixLength = strlen($packedIp) * 8;
        if ($prefixLength < 0 || $prefixLength > $maximumPrefixLength) {
            return false;
        }

        $completeBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if (substr($packedIp, 0, $completeBytes) !== substr($packedNetwork, 0, $completeBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;

        return (ord($packedIp[$completeBytes]) & $mask) === (ord($packedNetwork[$completeBytes]) & $mask);
    }

    private function normalizeHost(string $host) : string
    {
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            return substr($host, 1, -1);
        }

        return $host;
    }

    private function unpackIpv4(string $packed) : string
    {
        $ip = inet_ntop($packed);
        if ($ip === false) {
            throw new InvalidSafeUrl('Could not normalize embedded IPv4 address');
        }

        return $ip;
    }
}
