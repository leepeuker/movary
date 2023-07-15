<?php declare(strict_types=1);

namespace Movary\HttpController\Movie;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class MovieWatchProviderController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly TmdbApi $tmdbApi,
        private readonly Environment $twig,
    ) {
    }

    public function getWatchProviders(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];
        $country = $request->getGetParameters()['country'];

        $movie = $this->movieApi->fetchById($movieId);

        $watchProviders = $this->tmdbApi->getWatchProviders($movie->getTmdbId(), $country);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('component/watch-providers.html.twig', [
                'watchProviders' => $watchProviders->getBuy()
            ]),
        );
    }
}
