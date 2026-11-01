<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\UserApi;
use Movary\ValueObject\Config;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'user:bootstrap-admin',
    description: 'Create the initial administrator from environment settings when no users exist.',
)]
class UserBootstrapAdmin extends Command
{
    private const array REQUIRED_KEYS = [
        'INITIAL_ADMIN_EMAIL',
        'INITIAL_ADMIN_USERNAME',
        'INITIAL_ADMIN_PASSWORD',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly UserApi $userApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        if ($this->hasBootstrapConfiguration() === false) {
            return Command::SUCCESS;
        }

        if ($this->userApi->hasUsers() === true) {
            $this->generateOutput($output, 'Initial administrator already exists; skipping bootstrap.');

            return Command::SUCCESS;
        }

        foreach (self::REQUIRED_KEYS as $key) {
            if ($this->config->isProvided($key) === false) {
                $this->generateOutput($output, 'Initial administrator setup requires ' . $key . '.');

                return Command::FAILURE;
            }
        }

        try {
            $email = $this->config->getAsString('INITIAL_ADMIN_EMAIL');
            $username = $this->config->getAsString('INITIAL_ADMIN_USERNAME');
            $password = $this->config->getAsString('INITIAL_ADMIN_PASSWORD');

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || $username === '' || $password === '') {
                $this->generateOutput($output, 'Initial administrator settings must contain a valid email, username, and password.');

                return Command::FAILURE;
            }

            $this->userApi->createUser($email, $password, $username, true, true, true);
        } catch (Throwable $exception) {
            $this->logger->error('Could not create initial administrator.', ['exception' => $exception]);
            $this->generateOutput($output, 'Could not create initial administrator; check the configured values and application logs.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Initial administrator created.');

        return Command::SUCCESS;
    }

    private function hasBootstrapConfiguration() : bool
    {
        foreach (self::REQUIRED_KEYS as $key) {
            if ($this->config->isProvided($key) === true) {
                return true;
            }
        }

        return false;
    }
}
