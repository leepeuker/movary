<?php declare(strict_types=1);

namespace Movary\ValueObject\Http;

class StatusCode
{
    private function __construct(
        private readonly int $code,
        private readonly string $string,
    ) {
    }

    public static function createBadRequest() : self
    {
        return new self(400, 'Bad Request');
    }

    public static function createForbidden() : self
    {
        return new self(403, 'Forbidden');
    }

    public static function createInternalServerError() : self
    {
        return new self(500, 'Internal Server Error');
    }

    public static function createMethodNoContent() : self
    {
        return new self(204, 'No content');
    }

    public static function createMethodNotAllowed() : self
    {
        return new self(405, 'Method Not Allowed');
    }

    public static function createNoContent() : self
    {
        return new self(204, 'No Content');
    }

    public static function createNotFound() : self
    {
        return new self(404, 'Not Found');
    }

    public static function createOk() : self
    {
        return new self(200, 'OK');
    }

    public static function createMovedPermanently() : self
    {
        return new self(301, 'Moved Permanently');
    }

    public static function createSeeOther() : self
    {
        return new self(303, 'See Other');
    }

    public static function createUnauthorized() : self
    {
        return new self(401, 'Unauthorized');
    }

    public static function createTooManyRequests() : self
    {
        return new self(429, 'Too Many Requests');
    }

    public static function createUnsupportedMediaType() : self
    {
        // Returns HTTP code 415: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/415
        // Used for processing file upload
        return new self(415, 'Unsupported Media Type');
    }

    public function __toString() : string
    {
        return sprintf('HTTP/1.1 %d %s', $this->code, $this->string);
    }

    public function getCode() : int
    {
        return $this->code;
    }
}
