<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Movie;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MovieController
{
    public function __construct(
        private readonly TmdbApi $tmdbApi,
        private readonly MovieApi $movieApi
    ){ }

    public function getPosters(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];
        $movie = $this->movieApi->findById($movieId);
        if($movie === null) {
            return Response::createNotFound();
        }
        $tmdbId = $movie->getTmdbId();
        if($tmdbId === null) {
            return Response::createBadRequest();
        }
        $images = $this->tmdbApi->getImages($tmdbId)['posters'];
        return Response::createJson(Json::encode($images));
    }
}