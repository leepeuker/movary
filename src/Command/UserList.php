<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\UserApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'user:list',
    description: 'List all existing users.',
    aliases: ['user:list'],
    hidden: false,
)]
class UserList extends Command
{
    public function __construct(
        private readonly UserApi $userApi,
    ) {
        parent::__construct();
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $users = $this->userApi->fetchAll();

        foreach ($users as $user) {
            $this->generateOutput($output, sprintf('id: %s, email: %s', $user['id'], $user['email']));
        }

        return Command::SUCCESS;
    }
}
