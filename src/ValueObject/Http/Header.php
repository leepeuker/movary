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

    public static function createContentTypeJpeg(int $contentLength) : array
    {
        return [
            new self('Content-Type', 'image/jpeg'),
            new self('Content-Length', (string)$contentLength)
        ];
    }

    public static function createLocation(string $value) : self
    {
        return new self('Location', $value);
    }

    public function __toString() : string
    {
        return $this->name . ': ' . $this->value;
    }
}
