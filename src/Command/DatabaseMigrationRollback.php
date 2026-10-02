<?php declare(strict_types=1);

namespace Movary\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Movary\Service\DatabaseMigration\MigrationState;
use Movary\Service\DatabaseMigration\MigrationStateDetector;
use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:migration:rollback',
    description: 'Rollback last database migration.',
    aliases: ['database:migration:rollback'],
    hidden: false,
)]
class DatabaseMigrationRollback extends Command
{
    public function __construct(
        private readonly PhinxApplication $phinxApplication,
        private readonly string $phinxConfigurationFile,
        private readonly MigrationStateDetector $migrationStateDetector,
        private readonly DependencyFactory $dependencyFactory,
    ) {
        parent::__construct();
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $migrationState = $this->migrationStateDetector->detect();

        if ($migrationState === MigrationState::EMPTY) {
            $output->writeln('<error>There are no migrations to roll back.</error>');

            return self::FAILURE;
        }
        if ($migrationState === MigrationState::DOCTRINE) {
            $metadataStorage = $this->dependencyFactory->getMetadataStorage();
            $metadataStorage->ensureInitialized();
            $executedMigrations = $metadataStorage->getExecutedMigrations();

            if (count($executedMigrations) === 0) {
                $output->writeln('<error>There are no migrations to roll back.</error>');

                return self::FAILURE;
            }

            $arguments = [
                'versions' => [(string)$executedMigrations->getLast()->getVersion()],
                '--down' => true,
            ];
            $doctrineInput = new ArrayInput($arguments);
            $doctrineInput->setInteractive(false);

            return (new ExecuteCommand($this->dependencyFactory))->run($doctrineInput, $output);
        }
        if ($migrationState === MigrationState::UNEXPECTED) {
            $output->writeln('<error>The database migration state is not recognized.</error>');

            return self::FAILURE;
        }

        $command = $this->phinxApplication->find('rollback');

        $arguments = [
            'command' => $command,
            '--configuration' => $this->phinxConfigurationFile,
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}
