<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Api\MovieRatingController;
use Movary\HttpController\Api\RequestMapper\MovieRatingRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\PersonalRating;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieRatingController::class)]
#[AllowMockObjectsWithoutExpectations]
class MovieRatingControllerTest extends TestCase
{
    private MovieApi&MockObject $movieApi;
    private RequestMapper&MockObject $requestMapper;
    private MovieRatingRequestMapper&MockObject $movieRatingRequestMapper;
    private MovieRatingController $subject;

    protected function setUp() : void
    {
        $this->movieApi = $this->createMock(MovieApi::class);
        $this->requestMapper = $this->createMock(RequestMapper::class);
        $this->movieRatingRequestMapper = $this->createMock(MovieRatingRequestMapper::class);
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(34);
        $this->requestMapper->method('mapUsernameFromRoute')->willReturn($user);
        $this->subject = new MovieRatingController($this->movieApi, $this->movieRatingRequestMapper, $this->requestMapper);
    }

    public function testGetReturnsNotFoundForMissingRating() : void
    {
        $this->expectMovieExists();
        $this->movieApi->expects(self::once())->method('findUserRating')->with(12, 34)->willReturn(null);

        self::assertSame(404, $this->subject->getRating($this->createRequest())->getStatusCode()->getCode());
    }

    public function testGetReturnsRatingWithoutCaching() : void
    {
        $this->expectMovieExists();
        $this->movieApi->expects(self::once())->method('findUserRating')->with(12, 34)->willReturn(PersonalRating::create(8));

        $response = $this->subject->getRating($this->createRequest());

        self::assertSame('{"rating":8}', $response->getBody());
        self::assertContainsEquals(Header::createCacheControlNoStore(), $response->getHeaders());
        self::assertContainsEquals(Header::createPragmaNoCache(), $response->getHeaders());
    }

    public function testSaveUpdatesRating() : void
    {
        $request = $this->createRequest();
        $this->expectMovieExists();
        $this->movieRatingRequestMapper->expects(self::once())->method('mapRating')->with($request)->willReturn(PersonalRating::create(8));
        $this->movieApi->expects(self::once())->method('updateUserRating')->with(
            12,
            34,
            self::callback(static fn(PersonalRating $rating) : bool => $rating->asInt() === 8),
        );

        self::assertSame(204, $this->subject->saveRating($request)->getStatusCode()->getCode());
    }

    public function testDeleteClearsRating() : void
    {
        $this->expectMovieExists();
        $this->movieApi->expects(self::once())->method('updateUserRating')->with(12, 34, null);

        self::assertSame(204, $this->subject->deleteRating($this->createRequest())->getStatusCode()->getCode());
    }

    private function createRequest() : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['movaryId' => '12', 'username' => 'User']);

        return $request;
    }

    private function expectMovieExists() : void
    {
        $this->movieApi->expects(self::once())->method('findById')->with(12)->willReturn($this->createStub(MovieEntity::class));
    }
}
