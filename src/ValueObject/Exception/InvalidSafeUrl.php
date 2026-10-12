<?php declare(strict_types=1);

namespace Movary\ValueObject\Exception;

class InvalidSafeUrl extends InvalidUrl
{
    private function __construct(string $message, private readonly string $userMessage)
    {
        parent::__construct($message);
    }

    public static function create(string $url) : self
    {
        return new self(
            'Not a valid safe url: ' . $url,
            'Enter a valid server URL including http:// or https:// and a hostname.',
        );
    }

    public static function createForDisallowedScheme(string $scheme) : self
    {
        return new self('URL scheme not allowed: ' . $scheme, 'Server URL must use http:// or https://.');
    }

    public static function createForDisallowedPort(int $port) : self
    {
        return new self(
            'Port not allowed: ' . $port,
            'Server URL uses an unsupported port. Allowed ports: 80, 443, 8096, 8920, and 32400.',
        );
    }

    public static function createForUnresolvedHostname(string $host) : self
    {
        return new self(
            'Could not resolve hostname: ' . $host,
            'Server hostname could not be resolved. Check the hostname and DNS configuration.',
        );
    }

    public static function createForInvalidResolvedAddress(string $host) : self
    {
        return new self(
            'Hostname resolved to an invalid IP address: ' . $host,
            'Server hostname resolved to an invalid IP address. Check the DNS configuration.',
        );
    }

    public static function createForDisallowedDestination(string $host, string $address) : self
    {
        return new self(
            'Destination is not allowed: ' . $host . ' -> ' . $address,
            'Server URL resolves to a private or reserved IP address.',
        );
    }

    public function getUserMessage() : string
    {
        return $this->userMessage;
    }
}
