<?php declare(strict_types=1);

namespace Movary\Command;

use Exception;
use Movary\Service\Export\ExportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserRatingExport extends Command
{
    protected static $defaultName = 'user:export:ratings';

    public function __construct(
        private readonly ExportService $exportService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Export the ratings of a user as a csv file.')
            ->addArgument('userId', InputArgument::REQUIRED, 'Id of user to export the ratings from.')
            ->addArgument('exportFilename', InputArgument::OPTIONAL, 'A full qualified file name for the export csv.', null);
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument('userId');
        $exportFilename = $input->getArgument('exportFilename');

        $this->generateOutput($output, 'Exporting ratings...');

        try {
            $exportFilename = $this->exportService->createExportRatingsCsv($userId, $exportFilename);
        } catch (Exception $e) {
            $this->logger->error('Could not export rating', ['exception' => $e]);

            $this->generateOutput($output, 'Error: Could not export ratings');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Ratings exported to: ' . $exportFilename);

        return Command::SUCCESS;
    }
}
