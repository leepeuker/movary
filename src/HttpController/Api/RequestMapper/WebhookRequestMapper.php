<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use JsonException;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;

class WebhookRequestMapper
{
    public const int MAXIMUM_JSON_BYTES = 262144;

    public function mapJsonBody(Request $request) : array
    {
        $this->requireContentType($request, 'application/json');

        return $this->decodeJsonObject($request->getBody());
    }

    public function mapMultipartJsonField(Request $request, string $field) : array
    {
        $this->requireContentType($request, 'multipart/form-data');

        return $this->mapJsonField($request, $field);
    }

    public function mapJsonField(Request $request, string $field) : array
    {
        $value = $request->getPostParameters()[$field] ?? null;
        if (is_string($value) === false || $value === '') {
            throw InvalidApiRequest::invalidField($field, 'is required and must contain a JSON string');
        }

        return $this->decodeJsonObject($value);
    }

    private function decodeJsonObject(string $payload) : array
    {
        if (strlen($payload) > self::MAXIMUM_JSON_BYTES) {
            throw InvalidApiRequest::payloadTooLarge(self::MAXIMUM_JSON_BYTES);
        }

        try {
            $decodedPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidApiRequest::invalidWebhookPayload('must contain valid JSON');
        }

        if (is_array($decodedPayload) === false || str_starts_with(ltrim($payload), '{') === false) {
            throw InvalidApiRequest::invalidWebhookPayload('must contain a JSON object');
        }

        return $decodedPayload;
    }

    private function requireContentType(Request $request, string $expected) : void
    {
        $contentType = $request->getHeader('Content-Type');
        $mediaType = strtolower(trim(explode(';', (string)$contentType, 2)[0]));

        if ($mediaType !== $expected) {
            throw InvalidApiRequest::unsupportedWebhookMediaType($expected);
        }
    }
}
