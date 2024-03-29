<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class TmdbImageCacheDelete extends Command
{
    protected static $defaultName = 'tmdb:imageCache:delete';

    public function __construct(
        private readonly TmdbImageCache $imageCacheService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Delete cached images from themoviedb.org');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $this->generateOutput($output, 'Deleting cached images...');

            $this->imageCacheService->deleteCompleteCache();

            $this->generateOutput($output, 'Deleting cached images done.');
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete deleting image cache.');
            $this->logger->error('Could not complete deleting image cache.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
