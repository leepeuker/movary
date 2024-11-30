<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'tmdb:imageCache:cleanup',
    description: 'Delete outdated cached images which are not referenced anymore.',
    aliases: ['tmdb:imageCache:cleanup'],
    hidden: false,
)]
class TmdbImageCacheCleanup extends Command
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
            $this->generateOutput($output, 'Cleaning up cached images...');

            $deletionCounter = $this->imageCacheService->deletedOutdatedCache();

            $this->generateOutput($output, "Deleted [$deletionCounter] images from disk.");

            $this->generateOutput($output, 'Cleaning up cached images done.');
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete cleaning image cache up.');
            $this->logger->error('Could not complete cleaning image cache up.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
