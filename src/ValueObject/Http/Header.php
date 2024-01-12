<?php declare(strict_types=1);

namespace Movary\ValueObject\Http;

class Header
{
    private function __construct(
        private readonly string $name,
        private readonly string $value,
    ) {
    }

    public static function createContentTypeCss() : self
    {
        return new self('Content-Type', 'application/css');
    }

    public static function createContentTypeCsv() : self
    {
        return new self('Content-Type', 'text/csv');
    }

    public static function createContentTypeIco() : self
    {
        return new self('Content-Type', 'image/vnd.microsoft.icon');
    }

    public static function createContentTypeJs() : self
    {
        return new self('Content-Type', 'application/javascript');
    }

    public static function createContentTypeJson() : self
    {
        return new self('Content-Type', 'application/json');
    }

    public static function createLocation(string $value) : self
    {
        return new self('Location', $value);
    }

    public static function createContentTypePng() : self
    {
        return new self('Content-Type', 'image/png');
    }

    public static function createContentTypeWoff() : self
    {
        return new self('Content-Type', 'font/woff');
    }

    public static function createContentTypeWoff2() : self
    {
        return new self('Content-Type', 'font/woff2');
    }

    public function __toString() : string
    {
        return $this->name . ': ' . $this->value;
    }
}
