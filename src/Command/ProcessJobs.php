<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Worker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessJobs extends Command
{
    protected static $defaultName = 'jobs:process';

    public function __construct(
        private readonly Worker\Repository $repository,
        private readonly Worker\Service $workerService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Process job from the queue.');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        sleep(1); // For now to keep supervisor happy

        $this->generateOutput($output, 'Processing job...');

        try {
            $processedJobType = $this->processJob();
        } catch (\Exception $e) {
            $this->logger->error('Could not process job.', ['exception' => $e]);

            return Command::FAILURE;
        }

        $processedMessage = 'Nothing to process.';
        if ($processedJobType !== null) {
            $processedMessage = 'Processed job of type: ' . $processedJobType;
        }

        $this->generateOutput($output, $processedMessage);

        return Command::SUCCESS;
    }

    private function processJob() : ?string
    {
        $job = $this->repository->fetchOldestJob();

        if ($job === null) {
            return null;
        }

        $this->workerService->processJob($job);

        /** @noinspection PhpUnreachableStatementInspection */
        return $job->getType();
    }
}
