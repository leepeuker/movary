<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\Service\LoginAttemptLimiter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'user:login-attempt:list',
    description: 'List login attempts currently used for throttling.',
    aliases: ['user:login-attempt:list'],
    hidden: false,
)]
class UserLoginAttemptList extends Command
{
    public function __construct(
        private readonly LoginAttemptLimiter $loginAttemptLimiter,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $loginAttempts = $this->loginAttemptLimiter->fetchAttempts();
        } catch (Throwable $throwable) {
            $this->logger->error('Could not list login attempts.', ['exception' => $throwable]);
            $this->generateOutput($output, 'Could not list login attempts.');

            return Command::FAILURE;
        }

        if ($loginAttempts === []) {
            $this->generateOutput($output, 'No login attempts.');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($loginAttempts as $loginAttempt) {
            $rows[] = [$loginAttempt['id'], $loginAttempt['subjectHash'], (string)$loginAttempt['createdAt']];
        }

        (new Table($output))
            ->setHeaders(['ID', 'Subject hash', 'Created at'])
            ->setRows($rows)
            ->render();

        return Command::SUCCESS;
    }
}
