<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Letterboxd\SyncRatings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncLetterboxd extends Command
{
    private const OPTION_NAME_OVERWRITE = 'overwrite';

    protected static $defaultName = 'letterboxd:sync';

    public function __construct(
        private readonly SyncRatings $syncRatings,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Sync letterboxd.com movie ratings with local database')
            ->addArgument('ratingsCsvName', InputArgument::REQUIRED, 'Letterboxed rating csv file name (must be put into the tmp directory)')
            ->addOption(self::OPTION_NAME_OVERWRITE, [], InputOption::VALUE_NONE, 'Overwrite local data.');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $ratingsCsvPath = __DIR__ . '/../../tmp/' . $input->getArgument('ratingsCsvName');
        $overwriteExistingData = (bool)$input->getOption(self::OPTION_NAME_OVERWRITE);
        $verbose = $input->getOption('verbose');

        if (is_dir($ratingsCsvPath) === true || is_readable($ratingsCsvPath) === false) {
            $output->writeln('Csv file at the given path cannot be read: ' . $ratingsCsvPath);

            return Command::FAILURE;
        }

        try {
            $this->syncRatings->execute($ratingsCsvPath, $verbose, $overwriteExistingData);
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete letterboxd sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
