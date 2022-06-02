<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Api\Tmdb\Api;
use Movary\Application\Person;
use Movary\Util\Json;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class PersonController
{
    public function __construct(
        private readonly Person\Service\Select $personSelectService,
        private readonly Environment $twig,
        private readonly Api $tmdbApi
    ) {
    }

    public function fetchWatchedMoviesActedBy(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            Json::encode($this->personSelectService->findWatchedMoviesActedBy($personId)),
            [Header::createContentTypeJson()]
        );
    }

    public function fetchWatchedMoviesDirectedBy(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            Json::encode($this->personSelectService->findWatchedMoviesDirectedBy($personId)),
            [Header::createContentTypeJson()]
        );
    }

    public function renderPage(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        $person = $this->personSelectService->findById($personId);

        $tmdbMovieCredits = $this->tmdbApi->getMovieCreditsByPersonId($person->getTmdbId());
        $watchedMovieCredits = $this->personSelectService->findWatchedMoviesActedBy($personId);

        $movieCredits = [];
        foreach ($tmdbMovieCredits as $tmdbMovieCredit) {
            if ($tmdbMovieCredit['vote_count'] < 20) {
                continue;
            }

            $id = null;
            $rating10 = null;
            foreach ($watchedMovieCredits as $watchedMovieCredit) {
                if ($watchedMovieCredit['tmdb_id'] !== $tmdbMovieCredit['id']) {
                    continue;
                }

                $id = $watchedMovieCredit['id'];
                $rating10 = $watchedMovieCredit['rating_10'];

            }

            $movieCredits[] = [
                'id' => $id,
                'popularity' => $tmdbMovieCredit['popularity'],
                'poster_path' => $tmdbMovieCredit['poster_path'],
                'release_date' => $tmdbMovieCredit['release_date'],
                'rating_10' => $rating10,
            ];
        }

        usort($movieCredits, function($item1, $item2) {
            return $item2['release_date'] <=> $item1['release_date'];
        });

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/actor.html.twig', [
                'person' => $person,
                'moviesAsActor' => $movieCredits,
                'moviesAsDirector' => $this->personSelectService->findWatchedMoviesDirectedBy($personId),
            ]),
        );
    }
}
