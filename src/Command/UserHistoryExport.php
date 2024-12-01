<?php declare(strict_types=1);

namespace Movary\Command;

use Exception;
use Movary\Service\Export\ExportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'user:export:history',
    description: 'Export the history of a user as a csv file.',
    aliases: ['user:export:history'],
    hidden: false,
)]
class UserHistoryExport extends Command
{
    public function __construct(
        private readonly ExportService $exportService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'Id of user to export the history from.')
            ->addArgument('exportFilename', InputArgument::OPTIONAL, 'A full qualified file name for the export csv.', null);
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument('userId');
        $exportFilename = $input->getArgument('exportFilename');

        $this->generateOutput($output, 'Exporting history...');

        try {
            $exportFilename = $this->exportService->createExportHistoryCsv($userId, $exportFilename);
        } catch (Exception $e) {
            $this->logger->error('Could not export history', ['exception' => $e]);

            $this->generateOutput($output, 'Error: Could not export history');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'History exported to: ' . $exportFilename);

        return Command::SUCCESS;
    }
}
