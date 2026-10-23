<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Movie;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\Movie\Review\MovieReviewEntity;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\Movie\MovieReviewController;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieReviewController::class)]
#[AllowMockObjectsWithoutExpectations]
class MovieReviewControllerTest extends TestCase
{
    private MovieApi&MockObject $movieApi;

    private MovieReviewApi&MockObject $reviewApi;

    private MovieReviewController $subject;

    private UserEntity $user;

    protected function setUp() : void
    {
        $this->reviewApi = $this->createMock(MovieReviewApi::class);
        $this->movieApi = $this->createMock(MovieApi::class);
        $currentWebUser = $this->createStub(CurrentWebUser::class);
        $this->user = $this->createStub(UserEntity::class);
        $this->user->method('getId')->willReturn(34);
        $this->user->method('getName')->willReturn('alice');
        $currentWebUser->method('requireUser')->willReturn($this->user);
        $this->subject = new MovieReviewController($this->reviewApi, $this->movieApi, $currentWebUser);
    }

    public function testSaveRejectsDifferentRouteUser() : void
    {
        $this->reviewApi->expects(self::never())->method('save');

        $response = $this->subject->save($this->createRequest('bob', 12, ['review' => 'A review.']));

        self::assertSame(403, $response->getStatusCode()->getCode());
    }

    public function testFetchByTmdbIdReturnsCurrentUsersReview() : void
    {
        $movie = $this->createStub(MovieEntity::class);
        $movie->method('getId')->willReturn(12);
        $review = $this->createStub(MovieReviewEntity::class);
        $review->method('getReview')->willReturn('A review.');
        $review->method('containsSpoilers')->willReturn(true);
        $this->movieApi->expects(self::once())->method('findByTmdbId')->with(45)->willReturn($movie);
        $this->reviewApi->expects(self::once())->method('find')->with(12, 34)->willReturn($review);

        $response = $this->subject->fetchByTmdbId($this->createTmdbRequest(45));
        $responseBody = $response->getBody();

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertIsString($responseBody);
        self::assertSame(
            ['review' => 'A review.', 'containsSpoilers' => true],
            Json::decode($responseBody),
        );
    }

    public function testFetchByTmdbIdReturnsEmptyReviewForUnknownMovie() : void
    {
        $this->movieApi->expects(self::once())->method('findByTmdbId')->with(45)->willReturn(null);
        $this->reviewApi->expects(self::never())->method('find');

        $response = $this->subject->fetchByTmdbId($this->createTmdbRequest(45));
        $responseBody = $response->getBody();

        self::assertIsString($responseBody);
        self::assertSame(
            ['review' => null, 'containsSpoilers' => false],
            Json::decode($responseBody),
        );
    }

    public function testSaveReturnsNotFoundForMissingMovie() : void
    {
        $this->movieApi->expects(self::once())->method('findById')->with(12)->willReturn(null);
        $this->reviewApi->expects(self::never())->method('save');

        $response = $this->subject->save($this->createRequest('alice', 12, ['review' => 'A review.']));

        self::assertSame(404, $response->getStatusCode()->getCode());
    }

    public function testSaveCreatesOrUpdatesOwnedReview() : void
    {
        $this->movieApi->expects(self::once())->method('findById')->with(12)->willReturn(
            $this->createStub(MovieEntity::class),
        );
        $this->reviewApi->expects(self::once())->method('save')->with(12, 34, 'A review.', true);

        $response = $this->subject->save($this->createRequest(
            'alice',
            12,
            ['review' => 'A review.', 'containsSpoilers' => '1'],
        ));

        self::assertSame(204, $response->getStatusCode()->getCode());
    }

    public function testSaveReturnsValidationError() : void
    {
        $this->movieApi->method('findById')->willReturn($this->createStub(MovieEntity::class));
        $this->reviewApi
            ->expects(self::once())
            ->method('save')
            ->willThrowException(new \InvalidArgumentException('A movie review must not be empty.'));

        $response = $this->subject->save($this->createRequest('alice', 12, ['review' => '']));

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('A movie review must not be empty.', $response->getBody());
    }

    public function testDeleteRejectsDifferentRouteUser() : void
    {
        $this->reviewApi->expects(self::never())->method('delete');

        $response = $this->subject->delete($this->createRequest('bob', 12));

        self::assertSame(403, $response->getStatusCode()->getCode());
    }

    public function testDeleteRemovesOwnedReview() : void
    {
        $this->reviewApi->expects(self::once())->method('delete')->with(12, 34);

        $response = $this->subject->delete($this->createRequest('alice', 12));

        self::assertSame(204, $response->getStatusCode()->getCode());
    }

    /** @param array<string, string> $postParameters */
    private function createRequest(string $username, int $movieId, array $postParameters = []) : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => $username, 'id' => (string)$movieId]);
        $request->method('getPostParameters')->willReturn($postParameters);

        return $request;
    }

    private function createTmdbRequest(int $tmdbId) : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getGetParameters')->willReturn(['tmdbId' => (string)$tmdbId]);

        return $request;
    }
}
