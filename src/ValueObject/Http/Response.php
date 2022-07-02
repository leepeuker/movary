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
        private readonly array $headers = []
    ) {
    }

    public static function create(StatusCode $statusCode, ?string $body = null, array $headers = []) : self
    {
        return new self($statusCode, $body, $headers);
    }

    public static function createCsv(string $body) : self
    {
        return new self(StatusCode::createOk(), $body, [Header::createContentTypeCsv()]);
    }

    public static function createFoundRedirect(string $targetUrl) : self
    {
        return new self(StatusCode::createSeeOther(), null, [Header::createLocation($targetUrl)]);
    }

    public static function createJson(string $body) : self
    {
        return new self(StatusCode::createOk(), $body, [Header::createContentTypeJson()]);
    }

    public static function createNotFound() : self
    {
        return new self(StatusCode::createNotFound());
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
