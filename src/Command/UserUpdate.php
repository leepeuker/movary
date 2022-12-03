<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\User;
use Movary\Application\User\Exception\EmailNotUnique;
use Movary\Application\User\Exception\PasswordTooShort;
use Movary\Application\User\Exception\UsernameInvalidFormat;
use Movary\Application\User\Exception\UsernameNotUnique;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserUpdate extends Command
{
    protected static $defaultName = 'user:update';

    public function __construct(
        private readonly User\UserApi $userApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Update user data.')
            ->addArgument('userId', InputArgument::REQUIRED, 'ID of user')
            ->addOption('email', [], InputOption::VALUE_OPTIONAL, 'New email')
            ->addOption('name', [], InputOption::VALUE_OPTIONAL, 'New name')
            ->addOption('password', [], InputOption::VALUE_OPTIONAL, 'New password')
            ->addOption('coreAccountChangesDisabled', [], InputOption::VALUE_OPTIONAL, 'Set core account changes disabled status')
            ->addOption('traktUserName', [], InputOption::VALUE_OPTIONAL, 'New trakt user name')
            ->addOption('traktClientId', [], InputOption::VALUE_OPTIONAL, 'New trakt client id');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument('userId');

        try {
            $this->userApi->fetchUser($userId);
        } catch (\RuntimeException $e) {
            $this->generateOutput($output, 'User id does not exist: ' . $userId);

            return Command::FAILURE;
        }

        try {
            $email = $input->getOption('email');
            if ($email !== null) {
                $this->userApi->updateEmail($userId, $email);
            }

            $name = $input->getOption('name');
            if ($name !== null) {
                $this->userApi->updateName($userId, $name);
            }

            $password = $input->getOption('password');
            if ($password !== null) {
                $this->userApi->updatePassword($userId, $password);
            }

            $traktUserName = $input->getOption('traktUserName');
            if ($traktUserName !== null) {
                $traktUserName = $traktUserName === '' ? null : $traktUserName;

                $this->userApi->updateTraktUserName($userId, $traktUserName);
            }

            $traktClientId = $input->getOption('traktClientId');
            if ($traktClientId !== null) {
                $traktClientId = $traktClientId === '' ? null : $traktClientId;

                $this->userApi->updateTraktClientId($userId, $traktClientId);
            }

            $coreAccountChangesDisabled = $input->getOption('coreAccountChangesDisabled');
            if ($coreAccountChangesDisabled !== null) {
                $this->userApi->updateCoreAccountChangesDisabled($userId, (bool)$coreAccountChangesDisabled);
            }
        } catch (EmailNotUnique $e) {
            $this->generateOutput($output, 'Could not update user: Email already in use');

            return Command::FAILURE;
        } catch (PasswordTooShort $e) {
            $this->generateOutput($output, 'Could not update user: Password must contain at least 8 characters');

            return Command::FAILURE;
        } catch (UsernameInvalidFormat $e) {
            $this->generateOutput($output, 'Could not update user: Name must only consist of numbers and letters');

            return Command::FAILURE;
        } catch (UsernameNotUnique $e) {
            $this->generateOutput($output, 'Could not update user: Name already in use');

            return Command::FAILURE;
        } catch (\Throwable $t) {
            $this->logger->error('Could not change password.', ['exception' => $t]);

            $this->generateOutput($output, 'Could not update user.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Updated user.');

        return Command::SUCCESS;
    }
}
