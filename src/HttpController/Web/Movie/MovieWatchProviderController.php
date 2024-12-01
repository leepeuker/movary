<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Movie;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\MovieApi;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use RuntimeException;
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
        $streamType = $request->getGetParameters()['streamType'] ?? 'all';

        $movie = $this->movieApi->fetchById($movieId);

        $watchProviders = $this->tmdbApi->getWatchProviders($movie->getTmdbId(), $country);

        $watchProviders = match (true) {
            $streamType === 'rent' => $watchProviders->getRentProviders(),
            $streamType === 'ads' => $watchProviders->getAdsProviders(),
            $streamType === 'free' => $watchProviders->getFreeProviders(),
            $streamType === 'abo' => $watchProviders->getFlatrateProviders(),
            $streamType === 'buy' => $watchProviders->getBuyProviders(),
            $streamType === 'all' => $watchProviders->getAll(),
            default => throw new RuntimeException('Not supported stream type: ' . $streamType)
        };

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('component/watch-providers.html.twig', [
                'watchProviders' => $watchProviders
            ]),
        );
    }
}
