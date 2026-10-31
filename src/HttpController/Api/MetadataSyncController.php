<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Person\PersonApi;
use Movary\Service\Imdb\ImdbMovieRatingSync;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Service\Tmdb\SyncPerson;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MetadataSyncController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly PersonApi $personApi,
        private readonly SyncMovie $tmdbMovieSync,
        private readonly SyncPerson $tmdbPersonSync,
        private readonly ImdbMovieRatingSync $imdbMovieRatingSync,
    ) {
    }

    public function refreshMovieTmdbData(Request $request) : Response
    {
        $movie = $this->movieApi->findById((int)$request->getRouteParameters()['movaryId']);
        if ($movie === null) {
            return Response::createNotFound();
        }

        $this->tmdbMovieSync->syncMovie($movie->getTmdbId());

        return Response::createNoContent();
    }

    public function refreshMovieImdbRating(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['movaryId'];
        if ($this->movieApi->findById($movieId) === null) {
            return Response::createNotFound();
        }

        $this->imdbMovieRatingSync->syncMovieRating($movieId);

        return Response::createNoContent();
    }

    public function refreshPersonTmdbData(Request $request) : Response
    {
        $person = $this->personApi->findById((int)$request->getRouteParameters()['personId']);
        if ($person === null) {
            return Response::createNotFound();
        }

        $this->tmdbPersonSync->syncPerson($person->getTmdbId());

        return Response::createNoContent();
    }
}
