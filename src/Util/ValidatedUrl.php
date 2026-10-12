<?php declare(strict_types=1);

namespace Movary\Util;

use GuzzleHttp\Psr7\Uri;
use Movary\ValueObject\Url;

class ValidatedUrl
{
    public function __construct(
        private readonly string $scheme,
        private readonly string $host,
        private readonly int $port,
        private readonly string $ip,
    ) {
    }

    public function createCurlResolveEntry() : string
    {
        $ip = str_contains($this->ip, ':') ? '[' . $this->ip . ']' : $this->ip;

        return $this->host . ':' . $this->port . ':' . $ip;
    }

    public function createPinnedUrl(Url $url) : string
    {
        $host = str_contains($this->ip, ':') ? '[' . $this->ip . ']' : $this->ip;

        return (string)(new Uri((string)$url))->withHost($host);
    }

    public function getHost() : string
    {
        return $this->host;
    }

    public function getHostHeader() : string
    {
        $host = str_contains($this->host, ':') ? '[' . $this->host . ']' : $this->host;
        $defaultPort = $this->scheme === 'https' ? 443 : 80;

        return $this->port === $defaultPort ? $host : $host . ':' . $this->port;
    }

    public function getScheme() : string
    {
        return $this->scheme;
    }
}
