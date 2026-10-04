<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\Service\LoginAttemptLimiter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;

#[AsCommand(
    name: 'user:login-attempt:flush',
    description: 'Delete all login attempts used for throttling.',
    aliases: ['user:login-attempt:flush'],
    hidden: false,
)]
class UserLoginAttemptFlush extends Command
{
    public function __construct(
        private readonly LoginAttemptLimiter $loginAttemptLimiter,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Flush login attempts without confirmation.');
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
                    new ConfirmationQuestion('Flush all login attempts? [y/N] ', false),
                ) === true;
            if ($confirmed === false) {
                $this->generateOutput($output, 'No login attempts were flushed.');

                return Command::SUCCESS;
            }
        }

        try {
            $this->loginAttemptLimiter->flushAttempts();
        } catch (Throwable $throwable) {
            $this->logger->error('Could not flush login attempts.', ['exception' => $throwable]);
            $this->generateOutput($output, 'Could not flush login attempts.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'All login attempts flushed.');

        return Command::SUCCESS;
    }
}
