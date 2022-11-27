<?php declare(strict_types=1);

$container = require(__DIR__ . '/../bootstrap.php');

$application = $container->get(Symfony\Component\Console\Application::class);
$application->add($container->get(Movary\Command\TraktImport::class));
$application->add($container->get(Movary\Command\TmdbSync::class));
$application->add($container->get(Movary\Command\TmdbImageCacheRefresh::class));
$application->add($container->get(Movary\Command\TmdbImageCacheDelete::class));
$application->add($container->get(Movary\Command\DatabaseMigrationStatus::class));
$application->add($container->get(Movary\Command\DatabaseMigrationMigrate::class));
$application->add($container->get(Movary\Command\DatabaseMigrationRollback::class));
$application->add($container->get(Movary\Command\UserCreate::class));
$application->add($container->get(Movary\Command\UserDelete::class));
$application->add($container->get(Movary\Command\UserUpdate::class));
$application->add($container->get(Movary\Command\UserList::class));
$application->add($container->get(Movary\Command\ProcessJobs::class));
$application->add($container->get(Movary\Command\ImdbSync::class));

$application->run();
