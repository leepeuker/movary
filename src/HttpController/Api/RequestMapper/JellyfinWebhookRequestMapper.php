<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use DateTime;
use DateTimeZone;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;

class JellyfinWebhookRequestMapper
{
    public function __construct(private readonly WebhookRequestMapper $webhookRequestMapper)
    {
    }

    public function mapRequest(Request $request) : array
    {
        $payload = $this->webhookRequestMapper->mapJsonBody($request);
        $notificationType = $this->mapRequiredString($payload, 'NotificationType');
        $itemType = $this->mapRequiredString($payload, 'ItemType');

        if ($notificationType !== 'PlaybackStop' || $itemType !== 'Movie') {
            return $payload;
        }

        if (is_bool($payload['PlayedToCompletion'] ?? null) === false) {
            throw InvalidApiRequest::invalidField(
                'PlayedToCompletion',
                'is required and must be a boolean',
            );
        }

        $this->validateTimestamp($payload['UtcTimestamp'] ?? null);
        $this->validateOptionalTmdbId($payload['Provider_tmdb'] ?? null);

        return $payload;
    }

    private function mapRequiredString(array $payload, string $field) : string
    {
        $value = $payload[$field] ?? null;
        if (is_string($value) === false || $value === '') {
            throw InvalidApiRequest::invalidField($field, 'is required and must be a string');
        }

        return $value;
    }

    private function validateOptionalTmdbId(mixed $tmdbId) : void
    {
        if ($tmdbId === null || $tmdbId === '') {
            return;
        }

        $filteredTmdbId = filter_var($tmdbId, FILTER_VALIDATE_INT);
        if ($filteredTmdbId === false || $filteredTmdbId < 1) {
            throw InvalidApiRequest::invalidField('Provider_tmdb', 'must be a positive integer or empty');
        }
    }

    private function validateTimestamp(mixed $timestamp) : void
    {
        if (is_string($timestamp) === false) {
            throw InvalidApiRequest::invalidField('UtcTimestamp', 'is required and must be an ISO 8601 timestamp');
        }

        $timestampWithoutMicroseconds = preg_replace('/\.\d+Z/', '', $timestamp);
        $dateTime = DateTime::createFromFormat(
            'Y-m-d\TH:i:s',
            (string)$timestampWithoutMicroseconds,
            new DateTimeZone('UTC'),
        );
        if ($dateTime === false || $dateTime->format('Y-m-d\TH:i:s') !== $timestampWithoutMicroseconds) {
            throw InvalidApiRequest::invalidField('UtcTimestamp', 'must be a valid ISO 8601 timestamp');
        }
    }
}
