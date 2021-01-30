<?php declare(strict_types=1);

$container = require(__DIR__ . '/../bootstrap.php');

/** @var Movary\Command\Trakt\SyncWatchedMovies $service */
$syncWatched = $container->get(Movary\Command\Trakt\SyncWatchedMovies::class);

/** @var Movary\Command\Trakt\SyncRatings $service */
$syncRatings = $container->get(Movary\Command\Trakt\SyncRatings::class);

$syncWatched->run();
$syncRatings->run();
