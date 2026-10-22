<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use InvalidArgumentException;
use Movary\Api\Tmdb\Dto\TmdbPoster;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MoviePosterService;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MoviePosterController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly MoviePosterService $posterService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    public function fetchPosters(Request $request) : Response
    {
        $movie = $this->movieApi->findById((int)$request->getRouteParameters()['movaryId']);
        if ($movie === null) {
            return Response::createNotFound();
        }

        $posters = array_map(
            static fn(TmdbPoster $poster) : array => $poster->asArray(),
            $this->posterService->fetchAvailablePosters($movie),
        );

        return Response::createJson(Json::encode([
            'posters' => $posters,
            'hasOverride' => $movie->getTmdbPosterPathOverride() !== null,
        ]));
    }

    public function restoreDefault(Request $request) : Response
    {
        $movie = $this->movieApi->findById((int)$request->getRouteParameters()['movaryId']);
        if ($movie === null) {
            return Response::createNotFound();
        }

        $this->posterService->restoreDefault($movie);

        return Response::createNoContent();
    }

    public function selectPoster(Request $request) : Response
    {
        $movie = $this->movieApi->findById((int)$request->getRouteParameters()['movaryId']);
        if ($movie === null) {
            return Response::createNotFound();
        }

        $body = $this->requestMapper->mapJsonObject($request);
        $posterPath = $body['posterPath'] ?? null;
        if (is_string($posterPath) === false || $posterPath === '') {
            throw InvalidApiRequest::invalidField('posterPath', 'is required and must be a non-empty string');
        }

        try {
            $this->posterService->select($movie, $posterPath);
        } catch (InvalidArgumentException) {
            throw InvalidApiRequest::invalidField('posterPath', 'must identify an available TMDB poster');
        }

        return Response::createNoContent();
    }
}
