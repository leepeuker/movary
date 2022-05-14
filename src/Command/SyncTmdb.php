<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Tmdb\SyncMovieDetails;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTmdb extends Command
{
    private const OPTION_NAME_FORCE_HOURS = 'hours';

    protected static $defaultName = 'app:sync-tmdb';

    public function __construct(
        private readonly SyncMovieDetails $syncMovieDetails,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Sync trakt.tv movie history and rating with local database')
            ->addOption(self::OPTION_NAME_FORCE_HOURS, 'hours', InputOption::VALUE_REQUIRED, 'Hours since last updated.');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $hoursOption = $input->getOption(self::OPTION_NAME_FORCE_HOURS);
        $maxAgeInHours = $hoursOption !== null ? (int)$hoursOption : null;

        try {
            $this->syncMovieDetails->execute($maxAgeInHours);
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete tmdb sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
