<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\Dto\PlayedEntryDtoList;
use Movary\HttpController\Api\Dto\WatchlistRequestDto;
use Movary\HttpController\Api\PlayedController;
use Movary\HttpController\Api\RequestMapper\PlayedRequestMapper;
use Movary\HttpController\Api\ResponseMapper\PlayedResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlayedController::class)]
class PlayedControllerTest extends TestCase
{
    public function testPlayedMovieReadUsesMovieLevelData() : void
    {
        $request = $this->createStub(Request::class);
        $requestData = WatchlistRequestDto::create(12, null, 1, 24, 'title', SortOrder::createAsc());
        $requestMapper = $this->createMock(PlayedRequestMapper::class);
        $requestMapper->expects(self::once())->method('mapRequest')->with($request)->willReturn($requestData);
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi->expects(self::once())->method('fetchPlayedMoviesPaginated')->willReturn([]);
        $movieApi->expects(self::once())->method('fetchWatchDatesForMovies')->with(12, [])->willReturn([]);
        $movieApi->expects(self::once())->method('fetchPlayedMoviesCount')->willReturn(0);
        $responseMapper = $this->createMock(PlayedResponseMapper::class);
        $responseMapper->expects(self::once())->method('mapPlayedEntries')->with([], [])->willReturn(PlayedEntryDtoList::create());
        $subject = new PlayedController($movieApi, new PaginationElementsCalculator(), $requestMapper, $responseMapper);

        $response = $subject->getPlayed($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame(['played' => [], 'currentPage' => 1, 'maxPage' => 1], json_decode((string)$response->getBody(), true));
    }
}
