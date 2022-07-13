<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeUserTraktClientId extends Command
{
    protected static $defaultName = self::COMMAND_BASE_NAME . ':user:change-trakt-client-id';

    public function __construct(
        private readonly User\Api $userApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Change user trakt client id.')
            ->addArgument('userId', InputArgument::REQUIRED, 'ID of user')
            ->addArgument('traktClientId', InputArgument::REQUIRED, 'New trakt client id for user');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument('userId');
        $traktClientId = $input->getArgument('traktClientId');

        if (empty($traktClientId) === true) {
            $traktClientId = null;
        }

        try {
            $this->userApi->updateTraktClientId($userId, $traktClientId);
        } catch (\Throwable $t) {
            $this->logger->error('Could not change trakt client id.', ['exception' => $t]);

            $this->generateOutput($output, 'Could not update trakt client id.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Updated trakt client id.');
        return Command::SUCCESS;
    }
}
