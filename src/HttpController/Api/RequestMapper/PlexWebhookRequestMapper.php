<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;

class PlexWebhookRequestMapper
{
    private const array SUPPORTED_EVENTS = ['media.rate', 'media.scrobble'];

    public function __construct(private readonly WebhookRequestMapper $webhookRequestMapper)
    {
    }

    public function mapRequest(Request $request) : array
    {
        $payload = $this->webhookRequestMapper->mapMultipartJsonField($request, 'payload');
        $event = $this->mapRequiredString($payload, 'event');

        if (in_array($event, self::SUPPORTED_EVENTS, true) === false) {
            return $payload;
        }

        $this->validateSupportedEvent($payload, $event);

        return $payload;
    }

    private function validateSupportedEvent(array $payload, string $event) : void
    {
        $user = $payload['user'] ?? null;
        if (is_bool($user) === false) {
            throw InvalidApiRequest::invalidField('user', 'is required and must be a boolean');
        }

        $metadata = $this->mapRequiredObject($payload, 'Metadata');
        $librarySectionType = $this->mapRequiredString($metadata, 'librarySectionType', 'Metadata.');

        if ($user === false || $librarySectionType !== 'movie') {
            return;
        }

        $this->validateGuids($metadata);

        if ($event === 'media.scrobble') {
            $this->validatePositiveInteger($metadata['lastViewedAt'] ?? null, 'Metadata.lastViewedAt');
        } else {
            $this->validateRating($payload['rating'] ?? null);
        }

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

    private function validateGuids(array $metadata) : void
    {
        $guids = $metadata['Guid'] ?? null;
        if (is_array($guids) === false || array_is_list($guids) === false) {
            throw InvalidApiRequest::invalidField('Metadata.Guid', 'is required and must be an array');
        }

        foreach ($guids as $index => $guid) {
            if (is_array($guid) === false || is_string($guid['id'] ?? null) === false) {
                throw InvalidApiRequest::invalidField(
                    sprintf('Metadata.Guid[%d].id', $index),
                    'is required and must be a string',
                );
            }
        }
    }

    private function validatePositiveInteger(mixed $value, string $path) : void
    {
        $filteredValue = filter_var($value, FILTER_VALIDATE_INT);
        if ($filteredValue === false || $filteredValue < 1) {
            throw InvalidApiRequest::invalidField($path, 'must be a positive integer');
        }
    }

    private function validateRating(mixed $rating) : void
    {
        $filteredRating = filter_var($rating, FILTER_VALIDATE_INT);
        if ($filteredRating === false || $filteredRating < 1 || $filteredRating > 10) {
            throw InvalidApiRequest::invalidField('rating', 'must be an integer between 1 and 10');
        }
    }
}
