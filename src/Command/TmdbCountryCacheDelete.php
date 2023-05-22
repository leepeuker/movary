<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Api\Tmdb\Cache\TmdbIso6931Cache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class TmdbCountryCacheDelete extends Command
{
    protected static $defaultName = 'tmdb:countryCodeCache:delete';

    public function __construct(
        private readonly TmdbIso6931Cache $tmdbIso6931Cache,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Delete cached tmdb country codes');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $this->generateOutput($output, 'Deleting cached tmdb country codes...');

            $this->tmdbIso6931Cache->delete();

            $this->generateOutput($output, 'Deleting cached tmdb country codes done.');
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete deleting cached tmdb country codes.');
            $this->logger->error('Could not complete deleting cached tmdb country codes', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
