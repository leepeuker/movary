<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\Service\PasswordResetTokenService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'user:password:list-resets',
    description: 'List pending password resets.',
    aliases: ['user:password:list-resets'],
    hidden: false,
)]
class UserPasswordListResets extends Command
{
    public function __construct(
        private readonly PasswordResetTokenService $tokenService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $tokens = $this->tokenService->fetchPendingTokens();
        } catch (Throwable $throwable) {
            $this->logger->error('Could not list pending password resets.', ['exception' => $throwable]);
            $this->generateOutput($output, 'Could not list pending password resets.');

            return Command::FAILURE;
        }

        if ($tokens === []) {
            $this->generateOutput($output, 'No pending password resets.');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($tokens as $token) {
            $rows[] = [
                $token['userId'],
                $token['name'],
                $token['email'],
                (string)$token['createdAt'],
                (string)$token['expirationDate'],
            ];
        }

        (new Table($output))
            ->setHeaders(['User ID', 'Name', 'Email', 'Created at', 'Expires at'])
            ->setRows($rows)
            ->render();

        return Command::SUCCESS;
    }
}
