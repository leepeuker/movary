<?php declare(strict_types=1);

namespace Movary\Service\Imdb;

use Movary\Api\Imdb\ImdbWebScrapper;
use Movary\Domain\Movie\MovieApi;
use Psr\Log\LoggerInterface;
use Throwable;

class SyncMovie
{
    private const SLEEP_AFTER_FIRST_FAILED_REQUEST_IN_MS = 2000000;

    public function __construct(
        private readonly ImdbWebScrapper $imdbWebScrapper,
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function syncMovie(int $movieId, string $imdbId, string $movieTitle) : void
    {
        try {
            $imdbRating = $this->imdbWebScrapper->findRating($imdbId);
        } catch (Throwable) {
            /** @psalm-suppress ArgumentTypeCoercion */
            usleep(self::SLEEP_AFTER_FIRST_FAILED_REQUEST_IN_MS);

            try {
                $imdbRating = $this->imdbWebScrapper->findRating($imdbId);
            } catch (Throwable $t) {
                $this->logger->warning('Could not sync imdb rating for movie with id "' . $movieId . '". Error: ' . $t->getMessage(), ['exception' => $t]);

                return;
            }
        }

        $this->movieApi->updateImdbRating($movieId, $imdbRating['average'], $imdbRating['voteCount']);

        $this->logger->debug('Imdb sync: Updated imdb rating for movie', [
            'movieTitle' => $movieTitle,
            'average' => $imdbRating['average'],
            'voteCount' => $imdbRating['voteCount']
        ]);
    }
}
