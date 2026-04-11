<?php declare(strict_types=1);

namespace Movary\Util;

use Movary\ValueObject\Exception\InvalidSafeUrl;
use Movary\ValueObject\Url;

class UrlValidator
{
    private const array BLOCKED_HOSTS
        = [
            'metadata.google.internal',
            '169.254.169.254',  // AWS metadata
            'metadata.azure.internal',
            'metadata.rackspace.com',
            '169.254.169.254/latest/meta-data/',
            'metadata.service.consul',
            '169.254.169.254/latest',
        ];

    private const array ALLOWED_LOCALHOST
        = [
            'localhost',
            '127.0.0.1',
            '::1',
        ];

    private const array INTERNAL_DNS_PATTERNS
        = [
            '.internal',
            '.local',
            '.docker',
            '.corp',
            '.lan',
            '.home',
            '.priv',
        ];

    private const array ALLOWED_PORTS = [80, 443, 8096, 8920]; // 8096 and 8920 are Jellyfin defaults

    public function validateUrlIsSafe(Url $url): void
    {
        $parsedUrl = parse_url((string)$url);

        if ($parsedUrl === false || !isset($parsedUrl['host'])) {
            throw InvalidSafeUrl::create((string)$url);
        }

        $host = strtolower($parsedUrl['host']);
        $port = $parsedUrl['port'] ?? (($parsedUrl['scheme'] ?? null) === 'https' ? 443 : 80);

        $this->validateHost($host);
        $this->validatePort($port);
        $this->validateResolvedIp($host);
    }

    private function validateHost(string $host): void
    {
        // Block known internal/metadata endpoints
        foreach (self::BLOCKED_HOSTS as $blocked) {
            if (str_contains($host, $blocked)) {
                throw new InvalidSafeUrl('Blocked internal host: ' . $host);
            }
        }

        // Block localhost
        if ($this->isAllowedLocalhost($host)) {
            throw new InvalidSafeUrl('Localhost access not allowed: ' . $host);
        }

        // Block private IP ranges
        if ($this->isPrivateIp($host)) {
            throw new InvalidSafeUrl('Private IP address not allowed: ' . $host);
        }

        // Block internal DNS patterns
        if ($this->isInternalDns($host)) {
            throw new InvalidSafeUrl('Internal DNS name not allowed: ' . $host);
        }
    }

    private function validatePort(int $port): void
    {
        // Block suspicious ports (except common web ports)
        if (!in_array($port, self::ALLOWED_PORTS, true)) {
            throw new InvalidSafeUrl('Port not allowed: ' . $port);
        }
    }

    private function validateResolvedIp(string $host): void
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return;
        }

        // Resolve hostname to IP addresses
        $ips = gethostbynamel($host);

        if ($ips === false) {
            throw new InvalidSafeUrl('Could not resolve hostname: ' . $host);
        }

        foreach ($ips as $ip) {
            // Check if resolved IP is private (but allow localhost)
            if ($this->isPrivateIp($ip) && !$this->isAllowedLocalhost($ip)) {
                throw new InvalidSafeUrl('Hostname resolves to private IP: ' . $host . ' -> ' . $ip);
            }
        }
    }

    private function isAllowedLocalhost(string $host): bool
    {
        return in_array($host, self::ALLOWED_LOCALHOST, true);
    }

    private function isPrivateIp(string $host): bool
    {
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Use FILTER_FLAG_NO_PRIV_RANGE and FILTER_FLAG_NO_RES_RANGE to block private and reserved IPs
        return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private function isInternalDns(string $host): bool
    {
        foreach (self::INTERNAL_DNS_PATTERNS as $pattern) {
            if (str_ends_with($host, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
