<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Person\PersonApi;
use Movary\Domain\Person\PersonEntity;
use Movary\HttpController\Api\MetadataSyncController;
use Movary\Service\Imdb\ImdbMovieRatingSync;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Service\Tmdb\SyncPerson;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetadataSyncController::class)]
#[AllowMockObjectsWithoutExpectations]
class MetadataSyncControllerTest extends TestCase
{
    private MovieApi&MockObject $movieApi;
    private PersonApi&MockObject $personApi;
    private SyncMovie&MockObject $tmdbMovieSync;
    private SyncPerson&MockObject $tmdbPersonSync;
    private ImdbMovieRatingSync&MockObject $imdbMovieRatingSync;
    private MetadataSyncController $subject;

    protected function setUp() : void
    {
        $this->movieApi = $this->createMock(MovieApi::class);
        $this->personApi = $this->createMock(PersonApi::class);
        $this->tmdbMovieSync = $this->createMock(SyncMovie::class);
        $this->tmdbPersonSync = $this->createMock(SyncPerson::class);
        $this->imdbMovieRatingSync = $this->createMock(ImdbMovieRatingSync::class);
        $this->subject = new MetadataSyncController(
            $this->movieApi,
            $this->personApi,
            $this->tmdbMovieSync,
            $this->tmdbPersonSync,
            $this->imdbMovieRatingSync,
        );
    }

    public function testRefreshMovieTmdbDataSyncsExistingMovie() : void
    {
        $movie = $this->createStub(MovieEntity::class);
        $movie->method('getTmdbId')->willReturn(34);
        $this->movieApi->expects(self::once())->method('findById')->with(12)->willReturn($movie);
        $this->tmdbMovieSync->expects(self::once())->method('syncMovie')->with(34);

        self::assertSame(204, $this->subject->refreshMovieTmdbData($this->request())->getStatusCode()->getCode());
    }

    public function testRefreshMovieTmdbDataReturnsNotFound() : void
    {
        $this->movieApi->method('findById')->willReturn(null);
        $this->tmdbMovieSync->expects(self::never())->method('syncMovie');

        self::assertSame(404, $this->subject->refreshMovieTmdbData($this->request())->getStatusCode()->getCode());
    }

    public function testRefreshMovieImdbRatingSyncsExistingMovie() : void
    {
        $this->movieApi->expects(self::once())->method('findById')->with(12)->willReturn($this->createStub(MovieEntity::class));
        $this->imdbMovieRatingSync->expects(self::once())->method('syncMovieRating')->with(12);

        self::assertSame(204, $this->subject->refreshMovieImdbRating($this->request())->getStatusCode()->getCode());
    }

    public function testRefreshMovieImdbRatingReturnsNotFound() : void
    {
        $this->movieApi->method('findById')->willReturn(null);
        $this->imdbMovieRatingSync->expects(self::never())->method('syncMovieRating');

        self::assertSame(404, $this->subject->refreshMovieImdbRating($this->request())->getStatusCode()->getCode());
    }

    public function testRefreshPersonTmdbDataSyncsExistingPerson() : void
    {
        $person = $this->createStub(PersonEntity::class);
        $person->method('getTmdbId')->willReturn(56);
        $this->personApi->expects(self::once())->method('findById')->with(21)->willReturn($person);
        $this->tmdbPersonSync->expects(self::once())->method('syncPerson')->with(56);

        self::assertSame(204, $this->subject->refreshPersonTmdbData($this->request())->getStatusCode()->getCode());
    }

    public function testRefreshPersonTmdbDataReturnsNotFound() : void
    {
        $this->personApi->method('findById')->willReturn(null);
        $this->tmdbPersonSync->expects(self::never())->method('syncPerson');

        self::assertSame(404, $this->subject->refreshPersonTmdbData($this->request())->getStatusCode()->getCode());
    }

    private function request() : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['movaryId' => '12', 'personId' => '21']);

        return $request;
    }
}
