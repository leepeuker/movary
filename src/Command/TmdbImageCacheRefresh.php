<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Movary\JobQueue\JobQueueApi;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'tmdb:imageCache:refresh',
    description: 'Cache themoviedb.org images used for local movies to disk.',
    aliases: ['tmdb:imageCache:refresh'],
    hidden: false,
)]
class TmdbImageCacheRefresh extends Command
{
    private const string OPTION_NAME_FORCE = 'force';

    private const string OPTION_NAME_TYPE = 'type';

    public function __construct(
        private readonly TmdbImageCache $imageCacheService,
        private readonly JobQueueApi $jobQueueApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addOption(
                self::OPTION_NAME_TYPE,
                'type',
                InputOption::VALUE_OPTIONAL,
                'What type of images to cache: "movies" or "persons"',
            )
            ->addOption(
                self::OPTION_NAME_FORCE,
                'force',
                InputOption::VALUE_NONE,
                'Overwrite existing cached images',
            );
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $imageType = $input->getOption(self::OPTION_NAME_TYPE);
        $forceRefresh = $input->getOption(self::OPTION_NAME_FORCE);

        $jobId = $this->jobQueueApi->addTmdbImageCacheJob(jobStatus: JobStatus::createInProgress());

        try {
            switch ($imageType) {
                case null:
                    $this->cacheMovieImages($output, $forceRefresh);
                    $this->cachePersonImages($output, $forceRefresh);
                    break;
                case 'movies':
                    $this->cacheMovieImages($output, $forceRefresh);
                    break;
                case 'persons':
                    $this->cachePersonImages($output, $forceRefresh);
                    break;
                default:
                    $this->generateOutput($output, 'Not supported type: ' . $imageType);

                    return Command::FAILURE;
            }

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createDone());

            $this->generateOutput($output, 'Caching images done.');
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete tmdb image caching.');
            $this->logger->error('Could not complete tmdb image caching.', ['exception' => $t]);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createFailed());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function cacheMovieImages(OutputInterface $output, bool $forceRefresh = false) : void
    {
        $this->generateOutput($output, 'Caching movie images...');

        $cachedImageCount = $this->imageCacheService->cacheAllMovieImages($forceRefresh);

        $this->generateOutput($output, "Cached [$cachedImageCount] movie images.");
    }

    private function cachePersonImages(OutputInterface $output, bool $forceRefresh = false) : void
    {
        $this->generateOutput($output, 'Caching person images...');

        $cachedImageCount = $this->imageCacheService->cacheAllPersonImages($forceRefresh);

        $this->generateOutput($output, "Cached [$cachedImageCount] person images.");
    }
}
