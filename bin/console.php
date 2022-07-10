<?php declare(strict_types=1);

$container = require(__DIR__ . '/../bootstrap.php');

$application = $container->get(Symfony\Component\Console\Application::class);
$application->add($container->get(Movary\Command\SyncTrakt::class));
$application->add($container->get(Movary\Command\SyncTmdb::class));
$application->add($container->get(Movary\Command\SyncLetterboxd::class));
$application->add($container->get(Movary\Command\CreateUser::class));
$application->add($container->get(Movary\Command\ChangeUserPassword::class));
$application->add($container->get(Movary\Command\DatabaseMigration::class));

$application->run();
