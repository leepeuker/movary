<?php declare(strict_types=1);

namespace Movary\Service\DatabaseMigration;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class MigrationCoordinator
{
    public const string BASELINE_VERSION = 'Movary\\DatabaseMigration\\Version20260928000000';

    public function __construct(
        private readonly MigrationStateDetector $stateDetector,
        private readonly CutoverSchemaValidator $schemaValidator,
        private readonly DependencyFactory $dependencyFactory,
    ) {
    }

    public function migrate() : MigrationState
    {
        $state = $this->stateDetector->detect();

        match ($state) {
            MigrationState::EMPTY => $this->migrateDoctrine(),
            MigrationState::LEGACY_INCOMPLETE => null,
            MigrationState::LEGACY_READY => $this->transitionLegacyDatabase(),
            MigrationState::DOCTRINE => $this->migrateDoctrine(),
            MigrationState::UNEXPECTED => throw new RuntimeException(
                'The database migration state is not recognized. '
                . 'Restore the expected migration metadata or a backup before retrying.',
            ),
        };

        return $state;
    }

    private function transitionLegacyDatabase() : void
    {
        $this->schemaValidator->validate();

        $metadataStorage = $this->dependencyFactory->getMetadataStorage();
        $metadataStorage->ensureInitialized();

        $input = new ArrayInput([
            'version' => self::BASELINE_VERSION,
            '--add' => true,
        ]);
        $input->setInteractive(false);
        $exitCode = (new VersionCommand($this->dependencyFactory))->run($input, new NullOutput());
        if ($exitCode !== 0) {
            throw new RuntimeException('Could not record the validated Doctrine baseline.');
        }

        $this->migrateDoctrine();
    }

    private function migrateDoctrine() : void
    {
        $metadataStorage = $this->dependencyFactory->getMetadataStorage();
        $metadataStorage->ensureInitialized();

        $input = new ArrayInput([]);
        $input->setInteractive(false);
        $exitCode = (new MigrateCommand($this->dependencyFactory))->run($input, new NullOutput());
        if ($exitCode !== 0) {
            throw new RuntimeException('Doctrine migrations failed.');
        }
    }
}
