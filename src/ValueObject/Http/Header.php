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

    public static function createLocation(string $value) : self
    {
        return new self('Location', $value);
    }

    public static function createCorsHeaders(array $methods, string $origin = '*') : array
    {
        return [
            new self('Access-Control-Allow-Origin', $origin),
            new self('Access-Control-Allow-Credentials', 'true'),
            new self('Access-Control-Max-Age', '60'),
            new self('Access-Control-Allow-Headers', 'X-Movary-Client, Content-Type, Content-Type-Body, accept'),
            new self('Access-Control-Allow-Methods', implode(', ', $methods))
        ];
    }

    public function __toString() : string
    {
        return $this->name . ': ' . $this->value;
    }
}
