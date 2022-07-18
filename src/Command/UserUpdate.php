<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserUpdate extends Command
{
    protected static $defaultName = 'user:update';

    public function __construct(
        private readonly User\Api $userApi,
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
            ->addOption('password', [], InputOption::VALUE_OPTIONAL, 'New password')
            ->addOption('coreAccountChangesDisabled', [], InputOption::VALUE_OPTIONAL, 'Set core account changes disabled status')
            ->addOption('traktUserName', [], InputOption::VALUE_OPTIONAL, 'New trakt user name')
            ->addOption('traktClientId', [], InputOption::VALUE_OPTIONAL, 'New trakt client id');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument('userId');

        try {
            $email = $input->getOption('email');
            if ($email !== null) {
                $this->userApi->updateEmail($userId, $email);
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
        } catch (User\Exception\PasswordTooShort $t) {
            $this->generateOutput($output, "Error: Password must be at least {$t->getMinLength()} characters long.");

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
