<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Exception\PasswordInvalidEncoding;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Exception\UsernameNotUnique;
use Movary\Domain\User\UserApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'user:create',
    description: 'Create a new user.',
    aliases: ['user:create'],
    hidden: false,
)]
class UserCreate extends Command
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email address for user')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for user')
            ->addArgument('name', InputArgument::REQUIRED, 'Name for user')
            ->addArgument('isAdmin', InputArgument::OPTIONAL, 'Set user as admin', false)
            ->addOption('isEnabled', [], InputOption::VALUE_REQUIRED, 'Enable user account (1 or 0)', '1')
            ->addOption('emailVerified', [], InputOption::VALUE_REQUIRED, 'Mark email as verified (1 or 0)', '1');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $name = $input->getArgument('name');
        $isAdmin = (bool)$input->getArgument('isAdmin');
        $isEnabled = $this->parseBoolean($input->getOption('isEnabled'));
        $emailVerified = $this->parseBoolean($input->getOption('emailVerified'));

        if ($isEnabled === null || $emailVerified === null) {
            $this->generateOutput($output, 'isEnabled and emailVerified must be boolean values.');

            return Command::FAILURE;
        }

        try {
            $this->userApi->createUser(
                $email,
                $password,
                $name,
                $isAdmin,
                $isEnabled,
                $emailVerified,
            );
        } catch (EmailNotUnique $e) {
            $this->generateOutput($output, 'Could not create user: Email already in use');

            return Command::FAILURE;
        } catch (PasswordTooShort $e) {
            $this->generateOutput(
                $output,
                sprintf('Could not create user: Password must contain at least %d characters', $e->getMinLength()),
            );

            return Command::FAILURE;
        } catch (PasswordInvalidEncoding) {
            $this->generateOutput($output, 'Could not create user: Password must contain valid Unicode text');

            return Command::FAILURE;
        } catch (UsernameInvalidFormat $e) {
            $this->generateOutput($output, 'Could not create user: Name must only consist of numbers and letters');

            return Command::FAILURE;
        } catch (UsernameNotUnique $e) {
            $this->generateOutput($output, 'Could not create user: Name already in use');

            return Command::FAILURE;
        } catch (Throwable $t) {
            $this->logger->error('Could not create user.', ['exception' => $t]);

            $this->generateOutput($output, 'Could not create user.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'User created.');

        return Command::SUCCESS;
    }

    private function parseBoolean(mixed $value) : ?bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
