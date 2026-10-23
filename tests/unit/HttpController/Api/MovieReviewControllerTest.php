<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\Movie\Review\MovieReviewEntity;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Api\MovieReviewController;
use Movary\HttpController\Api\RequestMapper\MovieReviewRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\MovieReviewResponseMapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieReviewController::class)]
#[AllowMockObjectsWithoutExpectations]
class MovieReviewControllerTest extends TestCase
{
    private MovieApi&MockObject $movieApi;
    private MovieReviewApi&MockObject $movieReviewApi;
    private MovieReviewRequestMapper&MockObject $movieReviewRequestMapper;
    private MovieReviewController $subject;

    protected function setUp() : void
    {
        $this->movieApi = $this->createMock(MovieApi::class);
        $this->movieReviewApi = $this->createMock(MovieReviewApi::class);
        $this->movieReviewRequestMapper = $this->createMock(MovieReviewRequestMapper::class);

        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(34);
        $requestMapper = $this->createStub(RequestMapper::class);
        $requestMapper->method('mapUsernameFromRoute')->willReturn($user);

        $this->subject = new MovieReviewController(
            $this->movieApi,
            $this->movieReviewApi,
            $this->movieReviewRequestMapper,
            new MovieReviewResponseMapper(),
            $requestMapper,
        );
    }

    public function testGetReturnsNotFoundForUnknownMovie() : void
    {
        $this->movieApi->expects(self::once())->method('findById')->with(12)->willReturn(null);

        self::assertSame(404, $this->subject->getReview($this->createRequest())->getStatusCode()->getCode());
    }

    public function testGetReturnsNotFoundForMissingReview() : void
    {
        $this->expectMovieExists();
        $this->movieReviewApi->expects(self::once())->method('find')->with(12, 34)->willReturn(null);

        self::assertSame(404, $this->subject->getReview($this->createRequest())->getStatusCode()->getCode());
    }

    public function testGetReturnsReviewWithoutCaching() : void
    {
        $this->expectMovieExists();
        $this->movieReviewApi->method('find')->willReturn($this->createReview());

        $response = $this->subject->getReview($this->createRequest());

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame(
            '{"review":"A review","containsSpoilers":true,"createdAt":"2026-10-22 20:00:00","updatedAt":null}',
            $response->getBody(),
        );
        self::assertContainsEquals(Header::createCacheControlNoStore(), $response->getHeaders());
        self::assertContainsEquals(Header::createPragmaNoCache(), $response->getHeaders());
    }

    public function testSaveDelegatesMappedReview() : void
    {
        $request = $this->createRequest();
        $this->expectMovieExists();
        $this->movieReviewRequestMapper
            ->expects(self::once())
            ->method('mapReview')
            ->with($request)
            ->willReturn(['review' => 'A review', 'containsSpoilers' => true]);
        $this->movieReviewApi->expects(self::once())->method('save')->with(12, 34, 'A review', true);

        self::assertSame(204, $this->subject->saveReview($request)->getStatusCode()->getCode());
    }

    public function testDeleteDelegatesToReviewApi() : void
    {
        $this->expectMovieExists();
        $this->movieReviewApi->expects(self::once())->method('delete')->with(12, 34);

        self::assertSame(204, $this->subject->deleteReview($this->createRequest())->getStatusCode()->getCode());
    }

    private function createRequest() : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['movaryId' => '12', 'username' => 'User']);

        return $request;
    }

    private function createReview() : MovieReviewEntity
    {
        return MovieReviewEntity::createFromArray([
            'movie_id' => 12,
            'user_id' => 34,
            'review' => 'A review',
            'contains_spoilers' => 1,
            'created_at' => '2026-10-22 20:00:00',
            'updated_at' => null,
        ]);
    }

    private function expectMovieExists() : void
    {
        $this->movieApi
            ->expects(self::once())
            ->method('findById')
            ->with(12)
            ->willReturn($this->createStub(MovieEntity::class));
    }
}
