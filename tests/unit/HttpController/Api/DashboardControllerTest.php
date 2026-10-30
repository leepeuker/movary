<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Api\DashboardController;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\DashboardRowResponseMapper;
use Movary\HttpController\Api\ResponseMapper\WatchlistResponseMapper;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\Service\Dashboard\DashboardRowDataProvider;
use Movary\Service\Dashboard\Dto\DashboardRow;
use Movary\Service\Dashboard\Dto\DashboardRowList;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DashboardController::class)]
class DashboardControllerTest extends TestCase
{
    public function testOverviewReturnsTheWebDashboardValuesWithoutCaching() : void
    {
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(42);
        $mapper = $this->createMock(RequestMapper::class);
        $mapper->expects(self::once())->method('mapUsernameFromRoute')->willReturn($user);
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi->expects(self::once())->method('fetchTotalPlayCount')->with(42)->willReturn(12);
        $movieApi->expects(self::once())->method('fetchTotalPlayCountUnique')->with(42)->willReturn(8);
        $historyApi = $this->createMock(MovieHistoryApi::class);
        $historyApi->expects(self::once())->method('fetchTotalHoursWatched')->with(42)->willReturn(24);
        $historyApi->expects(self::once())->method('fetchAveragePlaysPerDay')->with(42)->willReturn(1.5);
        $historyApi->expects(self::once())->method('fetchAverageRuntime')->with(42)->willReturn(120);
        $historyApi->expects(self::once())->method('fetchAveragePersonalRating')->with(42)->willReturn(7.5);
        $subject = new DashboardController(
            $mapper,
            $this->createStub(DashboardFactory::class),
            $this->createStub(DashboardRowDataProvider::class),
            $this->createStub(DashboardRowResponseMapper::class),
            $this->createStub(Authentication::class),
            $movieApi,
            $historyApi,
        );

        $response = $subject->getOverview($this->request(['username' => 'alice']));

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame(
            '{"totalPlayCount":12,"uniqueMoviesCount":8,"totalHoursWatched":24,"averagePlaysPerDay":1.5,"averageRuntime":120,"averagePersonalRating":7.5}',
            $response->getBody(),
        );
        self::assertContainsEquals(Header::createCacheControlNoStore(), $response->getHeaders());
    }

    public function testManifestIncludesHiddenRowsInOrderWithoutFetchingData() : void
    {
        $user = $this->createStub(UserEntity::class);
        $mapper = $this->createStub(RequestMapper::class);
        $mapper->method('mapUsernameFromRoute')->willReturn($user);
        $factory = $this->createStub(DashboardFactory::class);
        $factory->method('createDashboardRowsForUser')->willReturn(DashboardRowList::create(
            DashboardRow::createWatchlist(false),
            DashboardRow::createLastPlays(),
        ));
        $provider = $this->createMock(DashboardRowDataProvider::class);
        $provider->expects(self::never())->method('fetch');
        $subject = new DashboardController($mapper, $factory, $provider, $this->createStub(DashboardRowResponseMapper::class), $this->createStub(Authentication::class), $this->createStub(MovieApi::class), $this->createStub(MovieHistoryApi::class));

        $response = $subject->getRows($this->request(['username' => 'alice']));
        $responseBody = $response->getBody();
        self::assertNotNull($responseBody);
        $body = json_decode($responseBody, true);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame([
            ['key' => 'latest-watchlist', 'title' => 'Latest in Watchlist', 'position' => 0, 'visible' => false, 'initiallyExpanded' => false, 'href' => '/api/v1/users/alice/dashboard/rows/latest-watchlist'],
            ['key' => 'last-plays', 'title' => 'Last Plays', 'position' => 1, 'visible' => true, 'initiallyExpanded' => true, 'href' => '/api/v1/users/alice/dashboard/rows/last-plays'],
        ], $body['rows']);
    }

    public function testHiddenRowReturnsNotFoundWithoutFetchingData() : void
    {
        $user = $this->createStub(UserEntity::class);
        $mapper = $this->createStub(RequestMapper::class);
        $mapper->method('mapUsernameFromRoute')->willReturn($user);
        $factory = $this->createStub(DashboardFactory::class);
        $factory->method('createDashboardRowsForUser')->willReturn(DashboardRowList::create(DashboardRow::createLastPlays(false)));
        $provider = $this->createMock(DashboardRowDataProvider::class);
        $provider->expects(self::never())->method('fetch');
        $subject = new DashboardController($mapper, $factory, $provider, $this->createStub(DashboardRowResponseMapper::class), $this->createStub(Authentication::class), $this->createStub(MovieApi::class), $this->createStub(MovieHistoryApi::class));

        $response = $subject->getRow($this->request(['username' => 'alice', 'rowKey' => 'last-plays']));

        self::assertSame(404, $response->getStatusCode()->getCode());
    }

    public function testUnknownRowReturnsNotFoundWithoutFetchingData() : void
    {
        $user = $this->createStub(UserEntity::class);
        $mapper = $this->createStub(RequestMapper::class);
        $mapper->method('mapUsernameFromRoute')->willReturn($user);
        $factory = $this->createStub(DashboardFactory::class);
        $factory->method('createDashboardRowsForUser')->willReturn(DashboardRowList::create(DashboardRow::createLastPlays()));
        $provider = $this->createMock(DashboardRowDataProvider::class);
        $provider->expects(self::never())->method('fetch');
        $subject = new DashboardController($mapper, $factory, $provider, $this->createStub(DashboardRowResponseMapper::class), $this->createStub(Authentication::class), $this->createStub(MovieApi::class), $this->createStub(MovieHistoryApi::class));

        self::assertSame(404, $subject->getRow($this->request(['username' => 'alice', 'rowKey' => 'unknown']))->getStatusCode()->getCode());
    }

    public function testVisibleEmptyRowReturnsItemsAndUsesViewerId() : void
    {
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(42);
        $mapper = $this->createStub(RequestMapper::class);
        $mapper->method('mapUsernameFromRoute')->willReturn($user);
        $row = DashboardRow::createMostWatchedGenres();
        $factory = $this->createStub(DashboardFactory::class);
        $factory->method('createDashboardRowsForUser')->willReturn(DashboardRowList::create($row));
        $provider = $this->createMock(DashboardRowDataProvider::class);
        $provider->expects(self::once())->method('fetch')->with($row, 42, 7)->willReturn(['mostWatchedGenres' => []]);
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('authenticateApiToken')->willReturn(AuthenticatedUser::create(7, CredentialType::API_TOKEN));
        $responseMapper = new DashboardRowResponseMapper($this->createStub(WatchlistResponseMapper::class));
        $subject = new DashboardController($mapper, $factory, $provider, $responseMapper, $authentication, $this->createStub(MovieApi::class), $this->createStub(MovieHistoryApi::class));

        $response = $subject->getRow($this->request(['username' => 'alice', 'rowKey' => 'most-watched-genres']));

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame('{"key":"most-watched-genres","title":"Most watched Genres","items":[]}', $response->getBody());
    }

    private function request(array $parameters) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn($parameters);

        return $request;
    }
}
