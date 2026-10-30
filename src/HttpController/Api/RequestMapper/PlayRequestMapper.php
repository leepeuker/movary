<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;

class PlayRequestMapper
{
    public function __construct(
        private readonly RequestMapper $requestMapper,
        private readonly MovieHistoryLocationApi $locationApi,
    ) {
    }

    /** @return array{movieId: int, watchedAt: null|Date, position: null|int, comment: null|string, locationId: null|int} */
    public function mapCreation(Request $request, int $userId) : array
    {
        $data = $this->requestMapper->mapJsonObject($request);
        if (array_key_exists('watchedAt', $data) === false) {
            throw InvalidApiRequest::invalidField('watchedAt', 'is required');
        }
        if (array_key_exists('plays', $data) === true) {
            throw InvalidApiRequest::invalidField('plays', 'is not supported; create one play per request');
        }

        $locationId = $this->requestMapper->mapOptionalNullablePositiveInteger($data, 'locationId', 'locationId');
        $this->validateLocation($locationId, $userId);

        return [
            'movieId' => $this->requestMapper->mapExistingMovieId($data, 'play'),
            'watchedAt' => $this->requestMapper->mapNullableDate($data['watchedAt'], 'watchedAt'),
            'position' => $this->requestMapper->mapOptionalNullablePositiveInteger($data, 'position', 'position', 32767),
            'comment' => $this->requestMapper->mapOptionalNullableString($data, 'comment', 'comment'),
            'locationId' => $locationId,
        ];
    }

    /** @return array{watchedAt: null|Date, position: int, comment: null|string, locationId: null|int} */
    public function mapUpdate(Request $request, int $userId) : array
    {
        $data = $this->requestMapper->mapJsonObject($request);
        if (array_key_exists('watchedAt', $data) === false) {
            throw InvalidApiRequest::invalidField('watchedAt', 'is required');
        }
        if (array_key_exists('comment', $data) === false) {
            throw InvalidApiRequest::invalidField('comment', 'is required');
        }
        if (array_key_exists('locationId', $data) === false) {
            throw InvalidApiRequest::invalidField('locationId', 'is required');
        }

        $comment = $data['comment'];
        if ($comment !== null && is_string($comment) === false) {
            throw InvalidApiRequest::invalidField('comment', 'must be a string or null');
        }

        $locationId = $this->requestMapper->mapOptionalNullablePositiveInteger($data, 'locationId', 'locationId');
        $this->validateLocation($locationId, $userId);

        return [
            'watchedAt' => $this->requestMapper->mapNullableDate($data['watchedAt'], 'watchedAt'),
            'position' => $this->requestMapper->mapRequiredPositiveInteger($data, 'position', 'position', 32767),
            'comment' => $comment,
            'locationId' => $locationId,
        ];
    }

    private function validateLocation(?int $locationId, int $userId) : void
    {
        if ($locationId !== null && $this->locationApi->findLocationById($locationId)?->getUserId() !== $userId) {
            throw InvalidApiRequest::invalidField('locationId', 'must belong to the user');
        }
    }
}
