<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\DashboardController;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\Service\Dashboard\Dto\DashboardRow;
use Movary\Service\Dashboard\Dto\DashboardRowList;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\StatusCode;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

#[CoversClass(DashboardController::class)]
#[AllowMockObjectsWithoutExpectations]
class DashboardControllerTest extends TestCase
{
    private Authentication&MockObject $authenticationMock;

    private DashboardFactory&MockObject $dashboardFactoryMock;

    private MovieHistoryApi&MockObject $movieHistoryApiMock;

    private MovieApi&MockObject $movieApiMock;

    private MovieWatchlistApi&MockObject $movieWatchlistApiMock;

    private DashboardController $subject;

    private Environment&MockObject $twigMock;

    private UserApi&MockObject $userApiMock;

    private UserPageAuthorizationChecker&MockObject $userPageAuthorizationCheckerMock;

    protected function setUp() : void
    {
        $this->twigMock = $this->createMock(Environment::class);
        $this->movieHistoryApiMock = $this->createMock(MovieHistoryApi::class);
        $this->movieApiMock = $this->createMock(MovieApi::class);
        $this->movieWatchlistApiMock = $this->createMock(MovieWatchlistApi::class);
        $this->userPageAuthorizationCheckerMock = $this->createMock(UserPageAuthorizationChecker::class);
        $this->dashboardFactoryMock = $this->createMock(DashboardFactory::class);
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->authenticationMock = $this->createMock(Authentication::class);

        $this->subject = new DashboardController(
            $this->twigMock,
            $this->movieHistoryApiMock,
            $this->movieApiMock,
            $this->movieWatchlistApiMock,
            $this->userPageAuthorizationCheckerMock,
            $this->dashboardFactoryMock,
            $this->userApiMock,
            $this->authenticationMock,
        );
    }

    public function testRenderFetchesOverviewAndExtendedRowData() : void
    {
        $user = $this->createUser(42);
        $dashboardRows = DashboardRowList::create(DashboardRow::createLastPlays());
        $lastPlays = [['id' => 7, 'title' => 'Arrival']];
        $this->userApiMock->expects(self::once())->method('fetchUserByName')->with('alice')->willReturn($user);
        $this->userApiMock->expects(self::once())->method('fetchUser')->with(42)->willReturn($user);
        $this->dashboardFactoryMock->expects(self::once())->method('createDashboardRowsForUser')->with($user)->willReturn($dashboardRows);
        $this->userPageAuthorizationCheckerMock
            ->method('fetchAllVisibleUsernamesForCurrentVisitor')
            ->willReturn([['name' => 'alice']]);
        $this->movieApiMock->expects(self::once())->method('fetchTotalPlayCount')->with(42)->willReturn(12);
        $this->movieApiMock->expects(self::once())->method('fetchTotalPlayCountUnique')->with(42)->willReturn(8);
        $this->movieHistoryApiMock->expects(self::once())->method('fetchTotalHoursWatched')->with(42)->willReturn(24);
        $this->movieHistoryApiMock->expects(self::once())->method('fetchAveragePersonalRating')->with(42)->willReturn(7.5);
        $this->movieHistoryApiMock->expects(self::once())->method('fetchAveragePlaysPerDay')->with(42)->willReturn(1.5);
        $this->movieHistoryApiMock->expects(self::once())->method('fetchAverageRuntime')->with(42)->willReturn(120);
        $this->movieHistoryApiMock->expects(self::never())->method('fetchFirstHistoryWatchDate');
        $this->movieHistoryApiMock->expects(self::once())->method('fetchLastPlays')->with(42)->willReturn($lastPlays);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/dashboard.html.twig', [
                'users' => [['name' => 'alice']],
                'totalPlayCount' => 12,
                'uniqueMoviesCount' => 8,
                'totalHoursWatched' => 24,
                'averagePersonalRating' => 7.5,
                'averagePlaysPerDay' => 1.5,
                'averageRuntime' => 120,
                'dashboardRows' => $dashboardRows,
                'lastPlays' => $lastPlays,
            ])
            ->willReturn('dashboard');

        $response = $this->subject->render($this->createRequest(['username' => 'alice']));

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame('dashboard', $response->getBody());
    }

    public function testRenderRowReturnsNotFoundForUnknownRow() : void
    {
        $user = $this->createUser(42);
        $this->userApiMock->method('fetchUserByName')->willReturn($user);
        $this->userApiMock->method('fetchUser')->willReturn($user);
        $this->dashboardFactoryMock
            ->method('createDashboardRowsForUser')
            ->willReturn(DashboardRowList::create(DashboardRow::createLastPlays()));
        $this->twigMock->expects(self::never())->method('render');

        $response = $this->subject->renderRow($this->createRequest(['username' => 'alice', 'rowId' => '999']));

        self::assertEquals(StatusCode::createNotFound(), $response->getStatusCode());
    }

    public function testRenderRowReturnsNotFoundForHiddenRow() : void
    {
        $user = $this->createUser(42);
        $this->userApiMock->method('fetchUserByName')->willReturn($user);
        $this->userApiMock->method('fetchUser')->willReturn($user);
        $this->dashboardFactoryMock
            ->method('createDashboardRowsForUser')
            ->willReturn(DashboardRowList::create(DashboardRow::createLastPlays(false)));
        $this->movieHistoryApiMock->expects(self::never())->method('fetchLastPlays');
        $this->twigMock->expects(self::never())->method('render');

        $response = $this->subject->renderRow($this->createRequest(['username' => 'alice', 'rowId' => '0']));

        self::assertEquals(StatusCode::createNotFound(), $response->getStatusCode());
    }

    public function testRenderRowFetchesAndRendersRequestedRow() : void
    {
        $user = $this->createUser(42);
        $dashboardRow = DashboardRow::createLastPlays();
        $lastPlays = [['id' => 7, 'title' => 'Arrival']];
        $this->userApiMock->expects(self::once())->method('fetchUserByName')->with('alice')->willReturn($user);
        $this->userApiMock->expects(self::once())->method('fetchUser')->with(42)->willReturn($user);
        $this->dashboardFactoryMock
            ->expects(self::once())
            ->method('createDashboardRowsForUser')
            ->with($user)
            ->willReturn(DashboardRowList::create($dashboardRow));
        $this->authenticationMock->method('isUserAuthenticatedWithCookie')->willReturn(false);
        $this->movieHistoryApiMock->expects(self::once())->method('fetchLastPlays')->with(42)->willReturn($lastPlays);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('component/dashboard/row.html.twig', [
                'dashboardRow' => $dashboardRow,
                'lastPlays' => $lastPlays,
            ])
            ->willReturn('dashboard row');

        $response = $this->subject->renderRow($this->createRequest(['username' => 'alice', 'rowId' => '0']));

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame('dashboard row', $response->getBody());
    }

    private function createRequest(array $routeParameters) : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn($routeParameters);

        return $request;
    }

    private function createUser(int $id) : UserEntity&MockObject
    {
        $user = $this->createMock(UserEntity::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }
}
