<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Movie;

use InvalidArgumentException;
use JsonException;
use Movary\Api\Tmdb\Dto\TmdbPoster;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MoviePosterService;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class MoviePosterController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly MoviePosterService $posterService,
    ) {
    }

    public function fetchPosters(Request $request) : Response
    {
        $movie = $this->movieApi->findById((int)$request->getRouteParameters()['id']);
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
        $movie = $this->movieApi->findById((int)$request->getRouteParameters()['id']);
        if ($movie === null) {
            return Response::createNotFound();
        }

        $this->posterService->restoreDefault($movie);

        return Response::createNoContent();
    }

    public function selectPoster(Request $request) : Response
    {
        $movie = $this->movieApi->findById((int)$request->getRouteParameters()['id']);
        if ($movie === null) {
            return Response::createNotFound();
        }

        try {
            $body = Json::decode($request->getBody());
        } catch (JsonException) {
            return $this->createUnprocessableResponse();
        }

        $posterPath = $body['posterPath'] ?? null;
        if (is_string($posterPath) === false || $posterPath === '') {
            return $this->createUnprocessableResponse();
        }

        try {
            $this->posterService->select($movie, $posterPath);
        } catch (InvalidArgumentException) {
            return $this->createUnprocessableResponse();
        }

        return Response::createNoContent();
    }

    private function createUnprocessableResponse() : Response
    {
        return Response::create(StatusCode::createUnprocessableEntity());
    }
}
