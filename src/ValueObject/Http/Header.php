<?php declare(strict_types=1);

namespace Movary\ValueObject\Http;

class Header
{
    private function __construct(
        private readonly string $name,
        private readonly string $value,
    ) {
    }

    public static function createContentTypeCsv() : self
    {
        return new self('Content-Type', 'text/csv');
    }

    public static function createContentTypeJson() : self
    {
        return new self('Content-Type', 'application/json');
    }

    public static function createContentTypeSVG() : self
    {
        return new self('Content-Type', 'image/svg+xml');
    }

    public static function createLocation(string $value) : self
    {
        return new self('Location', $value);
    }

    public static function createCache(int $cache_s) : self {
        return new self('cache-control', 'public, max-age=' . $cache_s);
    }

    public function __toString() : string
    {
        return $this->name . ': ' . $this->value;
    }
}
