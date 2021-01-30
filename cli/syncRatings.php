<?php declare(strict_types=1);

$container = require(__DIR__ . '/../bootstrap.php');

/** @var Movary\Command\Trakt\SyncRatings $service */
$service = $container->get(Movary\Command\Trakt\SyncRatings::class);

$service->run();
