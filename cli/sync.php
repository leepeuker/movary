<?php declare(strict_types=1);

$container = require(__DIR__ . '/../bootstrap.php');

/** @var Movary\Api\Trakt\Api $api */
$api = $container->get(Movary\Api\Trakt\Api::class);

/** @var Movary\Application\Movie\Service\Create $movieCreateService */
$movieCreateService = $container->get(Movary\Application\Movie\Service\Create::class);
/** @var Movary\Application\Movie\Service\Select $movieSelectService */
$movieSelectService = $container->get(Movary\Application\Movie\Service\Select::class);
/** @var Movary\Application\Movie\Service\Update $movieUpdateService */
$movieUpdateService = $container->get(Movary\Application\Movie\Service\Update::class);

/** @var Movary\Application\Movie\History\Service\Create $movieHistoryCreateService */
$movieHistoryCreateService = $container->get(Movary\Application\Movie\History\Service\Create::class);
/** @var Movary\Application\Movie\History\Service\Delete $movieHistoryDeleteService */
$movieHistoryDeleteService = $container->get(Movary\Application\Movie\History\Service\Delete::class);

/** @var Movary\Api\Trakt\Cache\User\Movie\Rating\Service $cacheUserMovieRatingService */
$cacheUserMovieRatingService = $container->get(Movary\Api\Trakt\Cache\User\Movie\Rating\Service::class);
/** @var Movary\Api\Trakt\Cache\User\Movie\Watched\Service $cacheUserMovieWatchedService */
$cacheUserMovieWatchedService = $container->get(Movary\Api\Trakt\Cache\User\Movie\Watched\Service::class);

/** @var Movary\Api\Trakt\ValueObject\User\Movie\Watched\Dto $watchedMovie */
$watchedMovies = $api->getUserMoviesWatched('leepe')->sortByLastUpdatedAt();

$cachedLatestLastMovieUpdate = $cacheUserMovieWatchedService->findLatestLastUpdatedAt();

if ((string)$watchedMovies->getLatestLastUpdated() > (string)$cachedLatestLastMovieUpdate) {
    $cacheUserMovieRatingService->set($api->getUserMoviesRatings('leepe'));
}

foreach ($watchedMovies as $watchedMovie) {
    if ((string)$watchedMovie->getLastUpdated() <= (string)$cachedLatestLastMovieUpdate) {
        break;
    }

    $movie = $movieSelectService->findByTraktId($watchedMovie->getMovie()->getTraktId());
    $rating = $cacheUserMovieRatingService->findRatingByTraktId($watchedMovie->getMovie()->getTraktId());

    if ($movie === null) {
        $movie = $movieCreateService->create(
            $watchedMovie->getMovie()->getTitle(),
            $watchedMovie->getMovie()->getYear(),
            $rating,
            $watchedMovie->getMovie()->getTraktId(),
            $watchedMovie->getMovie()->getImdbId(),
            $watchedMovie->getMovie()->getTmdbId(),
        );

        echo 'Added movie: ' . $movie->getTitle() . "\n";
    } elseif ($movie->getRating() !== $rating) {
        $movie = $movieUpdateService->updateRating($movie->getId(), $rating);

        echo 'Updated rating for movie: ' . $movie->getTitle() . "\n";
    }

    $movieHistoryDeleteService->deleteByMovieId($movie->getId());

    /** @var Movary\Api\Trakt\ValueObject\User\Movie\History\Dto $movieHistoryEntry */
    foreach ($api->getUserMovieHistoryByMovieId('leepe', $watchedMovie->getMovie()->getTraktId()) as $movieHistoryEntry) {
        $movieHistoryCreateService->create($movie->getId(), $movieHistoryEntry->getWatchedAt());

        echo 'Added watch date for "' . $movieHistoryEntry->getMovie()->getTitle() . '": ' . $movieHistoryEntry->getWatchedAt() . "\n";
    }
}

if ((string)$watchedMovies->getLatestLastUpdated() > (string)$cachedLatestLastMovieUpdate) {
    $cacheUserMovieWatchedService->set($watchedMovies);
}
