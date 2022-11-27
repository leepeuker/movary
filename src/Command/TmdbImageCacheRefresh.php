<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TmdbImageCacheRefresh extends Command
{
    private const OPTION_NAME_FORCE = 'force';

    private const  OPTION_NAME_TYPE = 'type';

    protected static $defaultName = 'tmdb:imageCache:refresh';

    public function __construct(
        private readonly TmdbImageCache $imageCacheService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Cache themoviedb.org images used for local movies to disk.')
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

            $this->generateOutput($output, 'Caching images done.');
        } catch (\Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete tmdb image caching.');
            $this->logger->error('Could not complete tmdb image caching.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function cacheMovieImages(OutputInterface $output, bool $forceRefresh = false) : void
    {
        $this->generateOutput($output, 'Caching movie images...');

        $this->imageCacheService->cacheMovieImages($forceRefresh);
    }

    private function cachePersonImages(OutputInterface $output, bool $forceRefresh = false) : void
    {
        $this->generateOutput($output, 'Caching person images...');

        $this->imageCacheService->cachePersonImages($forceRefresh);
    }
}
