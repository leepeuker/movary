<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\User\Api;
use Movary\Application\User\Service;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUser extends Command
{
    protected static $defaultName = self::COMMAND_BASE_NAME . ':user:create';

    public function __construct(
        private readonly Service\ChangePassword $changePasswordService,
        private readonly Api $userApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Create a new user.')
            ->addArgument('email', InputArgument::REQUIRED, 'Email address for user')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for user')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name for user');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $name = $input->getArgument('name');

        try {
            $this->userApi->createUser($email, $password, $name);
        } catch (\Throwable $t) {
            $this->logger->error('Could not create user.', ['exception' => $t]);

            $this->generateOutput($output, 'Could not create user.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Created user.');

        return Command::SUCCESS;
    }
}
