<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'tmdb:imageCache:delete',
    description: 'Delete cached images from themoviedb.org',
    aliases: ['tmdb:imageCache:delete'],
    hidden: false,
)]
class TmdbImageCacheDelete extends Command
{
    public function __construct(
        private readonly TmdbImageCache $imageCacheService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
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
