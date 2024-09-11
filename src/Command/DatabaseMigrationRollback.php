<?php declare(strict_types=1);

namespace Movary\Command;

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
    ) {
        parent::__construct();
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $command = $this->phinxApplication->find('rollback');

        $arguments = [
            'command' => $command,
            '--configuration' => $this->phinxConfigurationFile,
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}
