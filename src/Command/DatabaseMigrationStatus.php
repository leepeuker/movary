<?php declare(strict_types=1);

namespace Movary\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Movary\Service\DatabaseMigration\MigrationState;
use Movary\Service\DatabaseMigration\MigrationStateDetector;
use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:migration:status',
    description: 'Status of database migrations.',
    aliases: ['database:migration:status'],
    hidden: false,
)]
class DatabaseMigrationStatus extends Command
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
            $output->writeln('The database is empty. Run database:migration:migrate to initialize it.');

            return self::SUCCESS;
        }
        if ($migrationState === MigrationState::LEGACY_READY) {
            $output->writeln(
                'The legacy database is ready for the Doctrine cutover. '
                . 'Run database:migration:migrate to complete it.',
            );

            return self::SUCCESS;
        }
        if ($migrationState === MigrationState::DOCTRINE) {
            return (new StatusCommand($this->dependencyFactory))->run(new ArrayInput([]), $output);
        }
        if ($migrationState === MigrationState::UNEXPECTED) {
            $output->writeln('<error>The database migration state is not recognized.</error>');

            return self::FAILURE;
        }

        $command = $this->phinxApplication->find('status');

        $arguments = [
            'command' => $command,
            '--configuration' => $this->phinxConfigurationFile,
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}
