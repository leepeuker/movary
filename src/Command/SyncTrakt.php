<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Trakt\SyncRatings;
use Movary\Application\Service\Trakt\SyncWatchedMovies;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTrakt extends Command
{
    protected static string $defaultName = 'app:sync-trakt';

    private SyncRatings $syncRatings;

    private SyncWatchedMovies $syncWatchedMovies;

    public function __construct(SyncRatings $syncRatings, SyncWatchedMovies $syncWatchedMovies)
    {
        parent::__construct();

        $this->syncRatings = $syncRatings;
        $this->syncWatchedMovies = $syncWatchedMovies;
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Creates a new user.')
            ->setHelp('This command allows you to create a user...');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->syncWatchedMovies->execute();
        $this->syncRatings->execute();

        return Command::SUCCESS;
    }
}
