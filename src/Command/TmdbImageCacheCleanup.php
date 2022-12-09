<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class TmdbImageCacheCleanup extends Command
{
    protected static $defaultName = 'tmdb:imageCache:cleanup';

    public function __construct(
        private readonly TmdbImageCache $imageCacheService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Delete outdated cached images which are not referenced anymore.');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $this->generateOutput($output, 'Cleaning up cached images...');

            $this->imageCacheService->deletedOutdatedCache();

            $this->generateOutput($output, 'Cleaning up cached images done.');
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete cleaning image cache up.');
            $this->logger->error('Could not complete cleaning image cache up.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
