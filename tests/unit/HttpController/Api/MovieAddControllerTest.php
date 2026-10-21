<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Api\Tmdb\Exception\TmdbResourceNotFound;
use Movary\Domain\Movie\MovieEntity;
use Movary\HttpController\Api\Dto\MovieAddRequestDto;
use Movary\HttpController\Api\MovieAddController;
use Movary\HttpController\Api\RequestMapper\MovieAddRequestMapper;
use Movary\Service\Tmdb\SyncMovie;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\StatusCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieAddController::class)]
class MovieAddControllerTest extends TestCase
{
    public function testAddsMappedMovie() : void
    {
        $request = $this->createStub(Request::class);
        $requestMapper = $this->createMock(MovieAddRequestMapper::class);
        $requestMapper
            ->expects(self::once())
            ->method('mapRequest')
            ->with($request)
            ->willReturn(MovieAddRequestDto::create(42));
        $movie = $this->createStub(MovieEntity::class);
        $movie->method('getId')->willReturn(24);
        $syncMovie = $this->createMock(SyncMovie::class);
        $syncMovie->expects(self::once())->method('syncMovie')->with(42)->willReturn($movie);

        $response = (new MovieAddController($syncMovie, $requestMapper))->addMovie($request);

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame('{"movaryId":24}', $response->getBody());
    }

    public function testMapsUnknownTmdbMovieToExistingError() : void
    {
        $request = $this->createStub(Request::class);
        $requestMapper = $this->createStub(MovieAddRequestMapper::class);
        $requestMapper->method('mapRequest')->willReturn(MovieAddRequestDto::create(42));
        $syncMovie = $this->createMock(SyncMovie::class);
        $syncMovie
            ->expects(self::once())
            ->method('syncMovie')
            ->with(42)
            ->willThrowException(TmdbResourceNotFound::create('/movie/42'));

        $response = (new MovieAddController($syncMovie, $requestMapper))->addMovie($request);

        self::assertEquals(StatusCode::createBadRequest(), $response->getStatusCode());
        self::assertSame(
            '{"error":"ProviderIdNotFound","message":"External provider found no movie for submitted id"}',
            $response->getBody(),
        );
    }
}
