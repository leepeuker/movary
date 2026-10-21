<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Exception;

use Movary\ValueObject\Http\StatusCode;
use RuntimeException;

class InvalidApiRequest extends RuntimeException
{
    private function __construct(
        private readonly string $errorType,
        string $message,
        private readonly ?StatusCode $statusCode = null,
    ) {
        parent::__construct($message);
    }

    public static function invalidJson() : self
    {
        return new self('InvalidJson', 'Request body must contain a valid JSON array.');
    }

    public static function invalidJsonObject() : self
    {
        return new self('InvalidJson', 'Request body must contain a valid JSON object.');
    }

    public static function invalidField(string $path, string $requirement) : self
    {
        return new self('InvalidRequest', sprintf('Field "%s" %s.', $path, $requirement));
    }

    public static function invalidWebhookPayload(string $requirement) : self
    {
        return new self('InvalidRequest', 'Webhook payload ' . $requirement . '.');
    }

    public static function movieNotFound(int $movieId) : self
    {
        return new self('MovieNotFound', sprintf('Movie with id %d does not exist.', $movieId));
    }

    public static function missingProviderId() : self
    {
        return new self('MissingProviderId', 'No external provider id submitted');
    }

    public static function payloadTooLarge(int $maximumBytes) : self
    {
        return new self(
            'PayloadTooLarge',
            sprintf('Webhook JSON payload must not exceed %d bytes.', $maximumBytes),
            StatusCode::createPayloadTooLarge(),
        );
    }

    public static function unsupportedWebhookMediaType(string $expected) : self
    {
        return new self(
            'UnsupportedMediaType',
            sprintf('Webhook Content-Type must be %s.', $expected),
            StatusCode::createUnsupportedMediaType(),
        );
    }

    public function getErrorType() : string
    {
        return $this->errorType;
    }

    public function getStatusCode() : StatusCode
    {
        return $this->statusCode ?? StatusCode::createBadRequest();
    }
}
