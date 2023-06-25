<?php declare(strict_types=1);

$container = require(__DIR__ . '/../bootstrap.php');

$application = $container->get(Symfony\Component\Console\Application::class);
$application->add($container->get(Movary\Command\CreatePublicStorageLink::class));
$application->add($container->get(Movary\Command\TraktCache::class));
$application->add($container->get(Movary\Command\TraktImport::class));
$application->add($container->get(Movary\Command\TmdbMovieSync::class));
$application->add($container->get(Movary\Command\TmdbPersonSync::class));
$application->add($container->get(Movary\Command\TmdbImageCacheRefresh::class));
$application->add($container->get(Movary\Command\TmdbImageCacheDelete::class));
$application->add($container->get(Movary\Command\TmdbImageCacheCleanup::class));
$application->add($container->get(Movary\Command\TmdbCountryCacheDelete::class));
$application->add($container->get(Movary\Command\TmdbCountryCacheRefresh::class));
$application->add($container->get(Movary\Command\DatabaseMigrationStatus::class));
$application->add($container->get(Movary\Command\DatabaseMigrationMigrate::class));
$application->add($container->get(Movary\Command\DatabaseMigrationRollback::class));
$application->add($container->get(Movary\Command\UserCreate::class));
$application->add($container->get(Movary\Command\UserDelete::class));
$application->add($container->get(Movary\Command\UserHistoryExport::class));
$application->add($container->get(Movary\Command\UserRatingExport::class));
$application->add($container->get(Movary\Command\UserUpdate::class));
$application->add($container->get(Movary\Command\UserList::class));
$application->add($container->get(Movary\Command\PlexWatchlistImport::class));
$application->add($container->get(Movary\Command\ProcessJobs::class));
$application->add($container->get(Movary\Command\ImdbSync::class));

$application->run();
