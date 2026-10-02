<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Service\DatabaseMigration\MigrationCoordinator;
use Movary\Service\DatabaseMigration\MigrationState;
use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:migration:migrate',
    description: 'Execute missing database migrations.',
    aliases: ['database:migration:migrate'],
    hidden: false,
)]
class DatabaseMigrationMigrate extends Command
{
    public function __construct(
        private readonly PhinxApplication $phinxApplication,
        private readonly string $phinxConfigurationFile,
        private readonly MigrationCoordinator $migrationCoordinator,
    ) {
        parent::__construct();
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $migrationState = $this->migrationCoordinator->migrate();

        if ($migrationState !== MigrationState::LEGACY_INCOMPLETE) {
            return self::SUCCESS;
        }

        $output->writeln('Applying remaining legacy database migrations.');
        $command = $this->phinxApplication->find('migrate');

        $arguments = [
            'command' => $command,
            '--configuration' => $this->phinxConfigurationFile,
        ];

        $exitCode = $command->run(new ArrayInput($arguments), $output);
        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        $migrationState = $this->migrationCoordinator->migrate();
        if ($migrationState === MigrationState::LEGACY_INCOMPLETE) {
            throw new \RuntimeException(
                'Legacy migrations completed without reaching the Doctrine cutover boundary.',
            );
        }

        return self::SUCCESS;
    }
}
