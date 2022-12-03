<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\User\UserApi;
use Movary\Application\User\Exception\EmailNotUnique;
use Movary\Application\User\Exception\PasswordTooShort;
use Movary\Application\User\Exception\UsernameInvalidFormat;
use Movary\Application\User\Exception\UsernameNotUnique;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserCreate extends Command
{
    protected static $defaultName = 'user:create';

    public function __construct(
        private readonly UserApi $userApi,
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
            ->addArgument('name', InputArgument::REQUIRED, 'Name for user');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $name = $input->getArgument('name');

        try {
            $this->userApi->createUser($email, $password, $name);
        } catch (EmailNotUnique $e) {
            $this->generateOutput($output, 'Could not create user: Email already in use');

            return Command::FAILURE;
        } catch (PasswordTooShort $e) {
            $this->generateOutput($output, 'Could not create user: Password must contain at least 8 characters');

            return Command::FAILURE;
        } catch (UsernameInvalidFormat $e) {
            $this->generateOutput($output, 'Could not create user: Name must only consist of numbers and letters');

            return Command::FAILURE;
        } catch (UsernameNotUnique $e) {
            $this->generateOutput($output, 'Could not create user: Name already in use');

            return Command::FAILURE;
        } catch (\Throwable $t) {
            $this->logger->error('Could not create user.', ['exception' => $t]);

            $this->generateOutput($output, 'Could not create user.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'User created.');

        return Command::SUCCESS;
    }
}
