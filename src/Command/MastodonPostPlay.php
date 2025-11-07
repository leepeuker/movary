<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Service\Mastodon\MastodonPostPlayService;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'mastodon:post:play',
    description: 'Post a user movie play to Mastodon.',
    aliases: ['mastodon:post:play'],
    hidden: false,
)]
class MastodonPostPlay extends Command
{
    private const string OPTION_NAME_USER_ID = 'userId';

    private const string OPTION_NAME_MOVIE_ID = 'movieId';

    private const string OPTION_NAME_WATCH_DATE = 'watchDate';

    public function __construct(
        private readonly MastodonPostPlayService $mastodonPostPlayService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addOption(self::OPTION_NAME_USER_ID, [], InputOption::VALUE_REQUIRED, 'Id of user.');
        $this->addOption(self::OPTION_NAME_MOVIE_ID, [], InputOption::VALUE_REQUIRED, 'Id of watched movie.');
        $this->addOption(self::OPTION_NAME_WATCH_DATE, [], InputOption::VALUE_REQUIRED, 'Watchdate of watched movie as Y-m-d string.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getOption(self::OPTION_NAME_USER_ID);
        if (empty($userId) === true) {
            $this->generateOutput($output, 'Missing option --userId');
            exit;
        }

        $movieId = (int)$input->getOption(self::OPTION_NAME_MOVIE_ID);
        if (empty($movieId) === true) {
            $this->generateOutput($output, 'Missing option --movieId');
            exit;
        }

        $watchDate = (string)$input->getOption(self::OPTION_NAME_WATCH_DATE);
        if ($watchDate == "today") {
            $watchDate = Date::create()->format("Y-m-d");
        }
        if (empty($watchDate) === true) {
            $this->generateOutput($output, 'Missing option --watchDate');
            exit;
        }

        try {
            $this->mastodonPostPlayService->postPlay($userId, $movieId, $watchDate);
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not post play to Mastodon.');
            $this->logger->error('Could not post play to Mastodon.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
