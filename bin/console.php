<?php declare(strict_types=1);

$container = require(__DIR__ . '/../bootstrap.php');

$application = $container->get(Symfony\Component\Console\Application::class);
$application->add($container->get(Movary\Command\SyncTrakt::class));
$application->add($container->get(Movary\Command\SyncTmdb::class));
// $application->add($container->get(Movary\Command\SyncLetterboxd::class));
$application->add($container->get(Movary\Command\DatabaseMigrationStatus::class));
$application->add($container->get(Movary\Command\DatabaseMigrationMigrate::class));
$application->add($container->get(Movary\Command\DatabaseMigrationRollback::class));
$application->add($container->get(Movary\Command\UserCreate::class));
$application->add($container->get(Movary\Command\UserDelete::class));
$application->add($container->get(Movary\Command\UserUpdate::class));
$application->add($container->get(Movary\Command\UserList::class));
$application->add($container->get(Movary\Command\ProcessJobs::class));

$application->run();
