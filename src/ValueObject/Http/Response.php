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
        private readonly ?array $headers = [],
    ) {
    }

    public static function create(StatusCode $statusCode, ?string $body = null, ?array $headers = []) : self
    {
        return new self($statusCode, $body, $headers);
    }

    public static function createBadRequest(?string $body = null, ?array $headers = []) : self
    {
        return new self(StatusCode::createBadRequest(), $body, $headers);
    }

    public static function createCsv(string $body) : self
    {
        return new self(StatusCode::createOk(), $body, [Header::createContentTypeCsv()]);
    }

    public static function createForbidden() : self
    {
        return new self(StatusCode::createForbidden());
    }

    public static function createForbiddenRedirect(string $redirectTarget) : self
    {
        $query = urlencode($redirectTarget);

        return new self(StatusCode::createForbidden(), null, [Header::createLocation('/login?redirect=' . $query)]);
    }

    public static function createJson(string $body, ?StatusCode $statusCode = null) : self
    {
        return new self($statusCode ?? StatusCode::createOk(), $body, [Header::createContentTypeJson()]);
    }

    public static function createMethodNotAllowed() : self
    {
        return new self(StatusCode::createMethodNotAllowed());
    }

    public static function createNoContent() : self
    {
        return new self(StatusCode::createMethodNoContent());
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

    public static function createUnauthorized(?string $message = null, ?array $headers = []) : self
    {
        return new self(StatusCode::createUnauthorized(), $message, $headers);
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
        return (array)$this->headers;
    }

    public function getStatusCode() : StatusCode
    {
        return $this->statusCode;
    }
}
