<?php declare(strict_types=1);

namespace Movary\Command;

use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseMigrationStatus extends Command
{
    protected static $defaultName = 'database:migration:status';

    public function __construct(
        private readonly PhinxApplication $phinxApplication,
        private readonly string $phinxConfigurationFile
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Status of database migrations.');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $command = $this->phinxApplication->find('status');

        $arguments = [
            'command' => $command,
            '--configuration' => $this->phinxConfigurationFile,
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}
