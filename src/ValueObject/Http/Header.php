<?php declare(strict_types=1);

namespace Movary\ValueObject\Http;

use InvalidArgumentException;

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

    public static function createContentTypeZip() : self
    {
        return new self('Content-Type', 'application/zip');
    }

    public static function createContentTypeSVG() : self
    {
        return new self('Content-Type', 'image/svg+xml');
    }

    public static function createLocation(string $value) : self
    {
        return new self('Location', $value);
    }

    public static function createCacheControlPrivate() : self
    {
        return new self('Cache-Control', 'private, no-cache');
    }

    public static function createRetryAfter(int $seconds) : self
    {
        return new self('Retry-After', (string)$seconds);
    }

    public static function createAttachment(string $filename) : self
    {
        if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*$/D', $filename) !== 1) {
            throw new InvalidArgumentException('Invalid attachment filename.');
        }

        return new self('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public static function createCache(int $maxAgeInSeconds) : self
    {
        return new self('Cache-Control', 'public, max-age=' . $maxAgeInSeconds);
    }

    public function __toString() : string
    {
        return $this->name . ': ' . $this->value;
    }

    public function getName() : string
    {
        return $this->name;
    }
}
