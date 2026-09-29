<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\UserApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Throwable;

#[AsCommand(
    name: 'user:password:set',
    description: 'Set a user password using hidden interactive input.',
    aliases: ['user:password:set'],
    hidden: false,
)]
class UserPasswordSet extends Command
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly PasswordResetTokenService $tokenService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addArgument('userId', InputArgument::REQUIRED, 'ID of user');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument('userId');

        try {
            if ($this->userApi->findUserById($userId) === null) {
                $this->generateOutput($output, 'User id does not exist: ' . $userId);

                return Command::FAILURE;
            }

            if ($input->isInteractive() === false) {
                $this->generateOutput($output, 'Password input requires an interactive terminal.');

                return Command::FAILURE;
            }

            $helper = $this->getHelper('question');
            if ($helper instanceof QuestionHelper === false) {
                $this->generateOutput($output, 'Password input is unavailable.');

                return Command::FAILURE;
            }

            $password = $this->askForPassword($helper, $input, $output, 'New password: ');
            $passwordConfirmation = $this->askForPassword($helper, $input, $output, 'Repeat password: ');

            if ($password !== $passwordConfirmation) {
                $this->generateOutput($output, 'Passwords do not match.');

                return Command::FAILURE;
            }

            $this->userApi->updatePassword($userId, $password);
            $this->tokenService->deleteTokenForUser($userId);
        } catch (PasswordTooShort $exception) {
            $this->generateOutput($output, sprintf('Password must contain at least %d characters.', $exception->getMinLength()));

            return Command::FAILURE;
        } catch (Throwable $throwable) {
            $this->logger->error('Could not change password.', ['exception' => $throwable]);
            $this->generateOutput($output, 'Could not change password.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Password updated.');

        return Command::SUCCESS;
    }

    private function askForPassword(
        QuestionHelper $helper,
        InputInterface $input,
        OutputInterface $output,
        string $prompt,
    ) : string {
        $question = new Question($prompt);
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);

        return is_string($password) === true ? $password : '';
    }
}
