<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\Service\ImageUrlService;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MovieDetailController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly ImageUrlService $imageUrlService,
    ) {
    }

    public function getMovie(Request $request) : Response
    {
        $movie = $this->movieApi->findById((int)$request->getRouteParameters()['movaryId']);
        if ($movie === null) {
            return Response::createNotFound();
        }

        return Response::createJson(Json::encode([
            'id' => $movie->getId(),
            'tmdbId' => $movie->getTmdbId(),
            'traktId' => $movie->getTraktId()?->asInt(),
            'imdbId' => $movie->getImdbId(),
            'title' => $movie->getTitle(),
            'originalTitle' => $movie->getOriginalTitle(),
            'releaseDate' => $movie->getReleaseDate()?->asDate(),
            'tagline' => $movie->getTagline(),
            'overview' => $movie->getOverview(),
            'originalLanguage' => $movie->getOriginalLanguage(),
            'runtime' => $movie->getRuntime(),
            'posterUrl' => $this->imageUrlService->generateImageSrcUrlFromParameters(
                $movie->getTmdbPosterPathEffective(),
                $movie->getPosterPath(),
                $movie->getTitle(),
            ),
            'tmdbRatingAverage' => $movie->getTmdbVoteAverage(),
            'tmdbRatingVoteCount' => $movie->getTmdbVoteCount(),
            'imdbRatingAverage' => $movie->getImdbRatingAverage(),
            'imdbRatingVoteCount' => $movie->getImdbVoteCount(),
        ]));
    }
}
