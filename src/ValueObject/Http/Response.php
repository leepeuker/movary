<?php declare(strict_types=1);

namespace Movary\ValueObject\Http;

class Response
{
    /**
     * @param array<Header> $headers
     */
    private function __construct(
        private readonly StatusCode $statusCode,
        private readonly ?string $body = null,
        private readonly array $headers = [],
    ) {
    }

    public static function create(StatusCode $statusCode, ?string $body = null, array $headers = []) : self
    {
        return new self($statusCode, $body, $headers);
    }

    public static function createBadRequest(?string $body = null) : self
    {
        return new self(StatusCode::createBadRequest(), $body);
    }

    public static function createCsv(string $body) : self
    {
        return new self(StatusCode::createOk(), $body, [Header::createContentTypeCsv()]);
    }

    public static function createForbidden() : self
    {
        return new self(StatusCode::createForbidden());
    }

    public static function createJson(string $body) : self
    {
        return new self(StatusCode::createOk(), $body, [Header::createContentTypeJson()]);
    }

    public static function createMethodNotAllowed() : self
    {
        return new self(StatusCode::createMethodNotAllowed());
    }

    public static function createNotFound() : self
    {
        return new self(StatusCode::createNotFound());
    }

    public static function createOk() : self
    {
        return new self(StatusCode::createOk());
    }

    public static function createSeeOther(string $targetUrl) : self
    {
        return new self(StatusCode::createSeeOther(), null, [Header::createLocation($targetUrl)]);
    }

    public static function createUnauthorized() : self
    {
        return new self(StatusCode::createUnauthorized());
    }

    public static function createUnsupportedMediaType() : self
    {
        return new self(StatusCode::createUnsupportedMediaType());
    }

    public function getBody() : ?string
    {
        return $this->body;
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function getStatusCode() : StatusCode
    {
        return $this->statusCode;
    }
}
