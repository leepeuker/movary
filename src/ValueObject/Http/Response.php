<?php declare(strict_types=1);

namespace Movary\ValueObject\Http;

class Response
{
    /** @var array<Header> */
    private readonly array $headers;

    /**
     * @param array<Header> $headers
     */
    private function __construct(
        private readonly StatusCode $statusCode,
        private readonly ?string $body = null,
        ?array $headers = [],
    ) {
        $headers = (array)$headers;
        foreach ($headers as $header) {
            if ($header->getName() === 'Cache-Control') {
                $this->headers = $headers;

                return;
            }
        }

        $this->headers = [...$headers, Header::createCacheControlPrivate()];
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

    public static function createForbiddenRedirect(string $redirectTarget, string $baseUrl) : self
    {
        $query = urlencode($redirectTarget);

        $loginUrl = rtrim($baseUrl, '/') . '/login?redirect=' . $query;

        return new self(StatusCode::createForbidden(), null, [Header::createLocation($loginUrl)]);
    }

    public static function createJson(string $body, ?StatusCode $statusCode = null) : self
    {
        return new self($statusCode ?? StatusCode::createOk(), $body, [Header::createContentTypeJson()]);
    }

    public static function createZipDownload(string $body, string $downloadName) : self
    {
        return new self(
            StatusCode::createOk(),
            body: $body,
            headers: [
                Header::createContentTypeZip(),
                Header::createAttachment($downloadName),
            ],
        );
    }

    public static function createSVG(string $body, ?StatusCode $statusCode = null, int $cacheDurationInSeconds = 0) : self
    {
        return new self(
            $statusCode ?? StatusCode::createOk(),
            $body,
            [
                Header::createContentTypeSVG(),
                Header::createCache($cacheDurationInSeconds)
            ],
        );
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

    public static function createMovedPermanently(string $targetUrl) : self
    {
        return new self(StatusCode::createMovedPermanently(), null, [Header::createLocation($targetUrl)]);
    }

    public static function createSeeOther(string $targetUrl) : self
    {
        return new self(StatusCode::createSeeOther(), null, [Header::createLocation($targetUrl)]);
    }

    public static function createUnauthorized(?string $message = null, ?array $headers = []) : self
    {
        return new self(StatusCode::createUnauthorized(), $message, $headers);
    }

    public static function createBearerUnauthorized() : self
    {
        return self::createUnauthorized(headers: [Header::createWwwAuthenticateBearer()]);
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
