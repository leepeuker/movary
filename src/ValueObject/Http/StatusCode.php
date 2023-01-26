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

    public static function createSeeOther() : self
    {
        return new self(303, 'See Other');
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
