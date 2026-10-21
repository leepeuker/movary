<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;

class KodiWebhookRequestMapper
{
    public function __construct(private readonly WebhookRequestMapper $webhookRequestMapper)
    {
    }

    public function mapRequest(Request $request) : array
    {
        $payload = $this->webhookRequestMapper->mapJsonBody($request);

        if (isset($payload['title']) === true && is_string($payload['title']) === false) {
            throw InvalidApiRequest::invalidField('title', 'must be a string or null');
        }

        $uniqueIds = $payload['uniqueIds'] ?? null;
        if ($uniqueIds === null) {
            return $payload;
        }
        if (is_array($uniqueIds) === false) {
            throw InvalidApiRequest::invalidField('uniqueIds', 'must be an object');
        }

        $tmdbId = $uniqueIds['tmdbId'] ?? null;
        if ($tmdbId === null || $tmdbId === '') {
            return $payload;
        }

        $filteredTmdbId = filter_var($tmdbId, FILTER_VALIDATE_INT);
        if ($filteredTmdbId === false || $filteredTmdbId < 1) {
            throw InvalidApiRequest::invalidField('uniqueIds.tmdbId', 'must be a positive integer or empty');
        }

        return $payload;
    }
}
