<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\Service\PasswordResetTokenService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;

#[AsCommand(
    name: 'user:password:revoke-all-resets',
    description: 'Revoke all pending password resets.',
    aliases: ['user:password:revoke-all-resets'],
    hidden: false,
)]
class UserPasswordRevokeAllResets extends Command
{
    public function __construct(
        private readonly PasswordResetTokenService $tokenService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Revoke all resets without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        if ($input->getOption('force') !== true) {
            if ($input->isInteractive() === false) {
                $this->generateOutput($output, 'Use --force when running without interaction.');

                return Command::FAILURE;
            }

            $helper = $this->getHelper('question');
            $confirmed = $helper instanceof QuestionHelper
                && $helper->ask(
                    $input,
                    $output,
                    new ConfirmationQuestion('Revoke all pending password resets? [y/N] ', false),
                ) === true;
            if ($confirmed === false) {
                $this->generateOutput($output, 'No password resets were revoked.');

                return Command::SUCCESS;
            }
        }

        try {
            $this->tokenService->deleteAllTokens();
        } catch (Throwable $throwable) {
            $this->logger->error('Could not revoke all password resets.', ['exception' => $throwable]);
            $this->generateOutput($output, 'Could not revoke all password resets.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'All password resets revoked.');

        return Command::SUCCESS;
    }
}
