<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use DateTime;
use DateTimeZone;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;

class EmbyWebhookRequestMapper
{
    public function __construct(private readonly WebhookRequestMapper $webhookRequestMapper)
    {
    }

    public function mapRequest(Request $request) : array
    {
        $payload = $this->webhookRequestMapper->mapJsonField($request, 'data');
        $event = $this->mapRequiredString($payload, 'Event');
        $item = $this->mapRequiredObject($payload, 'Item');
        $itemType = $this->mapRequiredString($item, 'Type', 'Item.');

        if ($event !== 'playback.stop' || $itemType !== 'Movie') {
            return $payload;
        }

        $this->validateOptionalString($item['Name'] ?? null, 'Item.Name');
        $this->validateOptionalTmdbId($item);
        $this->validateTimestamp($payload['Date'] ?? null);

        $playbackInfo = $this->mapRequiredObject($payload, 'PlaybackInfo');
        if (is_bool($playbackInfo['PlayedToCompletion'] ?? null) === false) {
            throw InvalidApiRequest::invalidField(
                'PlaybackInfo.PlayedToCompletion',
                'is required and must be a boolean',
            );
        }

        return $payload;
    }

    private function mapRequiredObject(array $payload, string $field) : array
    {
        $value = $payload[$field] ?? null;
        if (is_array($value) === false) {
            throw InvalidApiRequest::invalidField($field, 'is required and must be an object');
        }

        return $value;
    }

    private function mapRequiredString(array $payload, string $field, string $pathPrefix = '') : string
    {
        $value = $payload[$field] ?? null;
        if (is_string($value) === false || $value === '') {
            throw InvalidApiRequest::invalidField(
                $pathPrefix . $field,
                'is required and must be a string',
            );
        }

        return $value;
    }

    private function validateOptionalString(mixed $value, string $path) : void
    {
        if ($value !== null && is_string($value) === false) {
            throw InvalidApiRequest::invalidField($path, 'must be a string or null');
        }
    }

    private function validateOptionalTmdbId(array $item) : void
    {
        $providerIds = $item['ProviderIds'] ?? null;
        if ($providerIds === null) {
            return;
        }
        if (is_array($providerIds) === false) {
            throw InvalidApiRequest::invalidField('Item.ProviderIds', 'must be an object');
        }

        $tmdbId = $providerIds['Tmdb'] ?? null;
        if ($tmdbId === null || $tmdbId === '') {
            return;
        }

        $filteredTmdbId = filter_var($tmdbId, FILTER_VALIDATE_INT);
        if ($filteredTmdbId === false || $filteredTmdbId < 1) {
            throw InvalidApiRequest::invalidField('Item.ProviderIds.Tmdb', 'must be a positive integer or empty');
        }
    }

    private function validateTimestamp(mixed $timestamp) : void
    {
        if (is_string($timestamp) === false) {
            throw InvalidApiRequest::invalidField('Date', 'is required and must be an ISO 8601 timestamp');
        }

        $timestampWithoutMicroseconds = preg_replace('/\.\d+Z/', '', $timestamp);
        $dateTime = DateTime::createFromFormat(
            'Y-m-d\TH:i:s',
            (string)$timestampWithoutMicroseconds,
            new DateTimeZone('UTC'),
        );
        if ($dateTime === false || $dateTime->format('Y-m-d\TH:i:s') !== $timestampWithoutMicroseconds) {
            throw InvalidApiRequest::invalidField('Date', 'must be a valid ISO 8601 timestamp');
        }
    }
}
