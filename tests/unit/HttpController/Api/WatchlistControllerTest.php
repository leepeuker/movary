<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\RequestMapper\WatchlistRequestMapper;
use Movary\HttpController\Api\ResponseMapper\WatchlistResponseMapper;
use Movary\HttpController\Api\WatchlistController;
use Movary\Service\PaginationElementsCalculator;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(WatchlistController::class)]
#[AllowMockObjectsWithoutExpectations]
class WatchlistControllerTest extends TestCase
{
    private MovieApi&MockObject $movieApi;
    private MovieWatchlistApi&MockObject $watchlistApi;
    private WatchlistController $subject;

    protected function setUp() : void
    {
        $this->movieApi = $this->createMock(MovieApi::class);
        $this->watchlistApi = $this->createMock(MovieWatchlistApi::class);

        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(34);
        $requestMapper = $this->createStub(RequestMapper::class);
        $requestMapper->method('mapUsernameFromRoute')->willReturn($user);

        $this->subject = new WatchlistController(
            $this->movieApi,
            $this->watchlistApi,
            $this->createStub(PaginationElementsCalculator::class),
            $this->createStub(WatchlistRequestMapper::class),
            $this->createStub(WatchlistResponseMapper::class),
            $requestMapper,
        );
    }

    public function testAddItemRejectsUnknownMovie() : void
    {
        $this->movieApi->expects(self::once())->method('findById')->with(12)->willReturn(null);
        $this->watchlistApi->expects(self::never())->method('addMovieToWatchlist');

        self::assertSame(404, $this->subject->addMovieToWatchlist($this->createRequest())->getStatusCode()->getCode());
    }

    public function testAddItemSavesExistingMovie() : void
    {
        $this->movieApi->expects(self::once())->method('findById')->with(12)->willReturn($this->createStub(MovieEntity::class));
        $this->watchlistApi->expects(self::once())->method('addMovieToWatchlist')->with(34, 12);

        self::assertSame(204, $this->subject->addMovieToWatchlist($this->createRequest())->getStatusCode()->getCode());
    }

    public function testDeleteItemDoesNotLookUpMovie() : void
    {
        $this->movieApi->expects(self::never())->method('findById');
        $this->watchlistApi->expects(self::once())->method('removeMovieFromWatchlist')->with(34, 12);

        self::assertSame(204, $this->subject->deleteMovieFromWatchlist($this->createRequest())->getStatusCode()->getCode());
    }

    private function createRequest() : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['movaryId' => '12', 'username' => 'User']);

        return $request;
    }
}
