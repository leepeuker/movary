<?php declare(strict_types=1);

$container = require(__DIR__ . '/../bootstrap.php');

/** @var Movary\Command\Trakt\SyncWatchedMovies $service */
$service = $container->get(Movary\Command\Trakt\SyncWatchedMovies::class);

$service->run();
