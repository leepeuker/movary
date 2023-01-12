<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Util\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class CreatePublicStorageLink extends Command
{
    protected static $defaultName = 'storage:link';

    public function __construct(
        private readonly File $fileUtil,
        private readonly string $appStorageDirectory,
        private readonly string $appBaseDirectory,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Create the public storage symlink.');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $target = $this->appStorageDirectory . 'public';
        $link = $this->appBaseDirectory . 'public/storage';

        try {
            if ($this->fileUtil->fileExists($link) === false) {
                $this->fileUtil->createSymlink($target, $link);
            }
        } catch (Throwable $t) {
            $this->generateOutput($output, 'Could not create public storage symlink.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Public storage symlink created.');

        return Command::SUCCESS;
    }
}
