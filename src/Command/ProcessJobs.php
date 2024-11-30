<?php declare(strict_types=1);

namespace Movary\Command;

use Exception;
use Movary\JobQueue;
use Movary\Service\JobProcessor;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'jobs:process',
    description: 'Process job from the queue.',
    aliases: ['jobs:process'],
    hidden: false,
)]
class ProcessJobs extends Command
{
    private const string OPTION_NAME_MIN_RUNTIME = 'minRuntime';

    public function __construct(
        private readonly JobQueue\JobQueueApi $jobApi,
        private readonly JobProcessor $jobProcessor,
        private readonly LoggerInterface $logger,
        private readonly ?int $minRuntimeInSeconds = null,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addOption(self::OPTION_NAME_MIN_RUNTIME, 'minRuntime', InputOption::VALUE_REQUIRED, 'Minimum runtime of command.');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $minRuntime = $input->getOption(self::OPTION_NAME_MIN_RUNTIME) ?? $this->minRuntimeInSeconds;

        $timeStart = microtime(true);

        $this->generateOutput($output, 'Processing job...');

        try {
            $processedJobType = $this->processJob();
        } catch (Exception $e) {
            $this->logger->error('Could not process job.', ['exception' => $e]);

            return Command::FAILURE;
        }

        $processedMessage = 'Nothing to process.';
        if ($processedJobType !== null) {
            $processedMessage = 'Processed job of type: ' . $processedJobType;
        }

        $this->generateOutput($output, $processedMessage);

        $missingTime = (int)$minRuntime - (microtime(true) - $timeStart);
        if ($missingTime > 0) {
            $waitTime = max((int)ceil($missingTime * 1000000), 0);

            $this->generateOutput($output, 'Sleeping for ' . $waitTime / 1000000 . ' seconds to reach min runtime...');

            usleep($waitTime);
        }

        return Command::SUCCESS;
    }

    private function processJob() : ?JobType
    {
        $job = $this->jobApi->fetchOldestWaitingJob();

        if ($job === null) {
            return null;
        }

        try {
            $this->jobApi->setJobToInProgress($job->getId());

            $this->jobProcessor->processJob($job);

            $this->jobApi->updateJobStatus($job->getId(), JobStatus::createDone());
        } catch (Exception $e) {
            $this->jobApi->updateJobStatus($job->getId(), JobStatus::createFailed());

            throw $e;
        }

        return $job->getType();
    }
}
