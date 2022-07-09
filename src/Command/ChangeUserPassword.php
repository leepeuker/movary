<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\User\Service;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeUserPassword extends Command
{
    protected static $defaultName = self::COMMAND_BASE_NAME . ':change-admin-password';

    public function __construct(
        private readonly Service\ChangePassword $changePasswordService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Change the current admin password.')
            ->addArgument('password', InputArgument::REQUIRED, 'New admin password');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $password = $input->getArgument('password');

        try {
            $this->changePasswordService->changeAdminPassword($password);
        } catch (\Throwable $t) {
            $this->logger->error('Could not change admin password.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
