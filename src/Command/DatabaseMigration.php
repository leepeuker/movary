<?php declare(strict_types=1);

namespace Movary\Command;

use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseMigration extends Command
{
    private const OPTION_NAME_MIGRATE = 'migrate';

    private const OPTION_NAME_ROLLBACK = 'rollback';

    protected static $defaultName = self::COMMAND_BASE_NAME . ':database:migration';

    public function __construct(
        private readonly PhinxApplication $phinxApplication,
        private readonly string $phinxConfigurationFile
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Execute database migration')
            ->addOption(self::OPTION_NAME_MIGRATE, 'migrate', InputOption::VALUE_NONE, 'Run missing migrations')
            ->addOption(self::OPTION_NAME_ROLLBACK, 'rollback', InputOption::VALUE_NONE, 'Rollback last migration');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $migrate = (bool)$input->getOption(self::OPTION_NAME_MIGRATE);
        $rollback = (bool)$input->getOption(self::OPTION_NAME_ROLLBACK);

        $command = 'status';
        if ($migrate === true) {
            $command = 'migrate';
        } elseif ($rollback === true) {
            $command = 'rollback';
        }

        $command = $this->phinxApplication->find($command);

        $arguments = [
            'command' => $command,
            '--configuration' => $this->phinxConfigurationFile,
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}
