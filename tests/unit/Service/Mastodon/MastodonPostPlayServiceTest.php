<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Mastodon;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Mastodon\MastodonPostPlayService;
use Movary\Service\Mastodon\MastodonPostService;
use Movary\Service\SlugifyService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(MastodonPostPlayService::class)]
class MastodonPostPlayServiceTest extends TestCase
{
    public function testMissingPlaySkipsPostAndLogsInfo() : void
    {
        $historyApi = $this->createMock(MovieHistoryApi::class);
        $historyApi->expects(self::once())->method('findPlayById')->with(31, 7)->willReturn(null);

        $mastodonPostService = $this->createMock(MastodonPostService::class);
        $mastodonPostService->expects(self::never())->method('postMessageForUser');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'Mastodon play post skipped: no play found for the requested user and movie.',
                ['playId' => 31, 'userId' => 7, 'movieId' => 11],
            );

        $subject = new MastodonPostPlayService(
            $mastodonPostService,
            $this->createStub(MovieApi::class),
            $this->createStub(UserApi::class),
            $this->createStub(SlugifyService::class),
            $this->createStub(ApplicationUrlService::class),
            $historyApi,
            $logger,
        );

        $subject->postPlay(7, 11, null, 31);
    }
}
