<?php declare(strict_types=1);

namespace Movary\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:migration:generate',
    description: 'Generate a new Doctrine database migration.',
    aliases: ['database:migration:generate'],
    hidden: false,
)]
class DatabaseMigrationGenerate extends Command
{
    public function __construct(private readonly DependencyFactory $dependencyFactory)
    {
        parent::__construct();
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $doctrineInput = new ArrayInput([]);
        $doctrineInput->setInteractive(false);

        return (new GenerateCommand($this->dependencyFactory))->run($doctrineInput, $output);
    }
}
