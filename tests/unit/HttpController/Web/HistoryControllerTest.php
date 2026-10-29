<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\History\MovieHistoryEntity;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\HistoryController;
use Movary\Service\PaginationElementsCalculator;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\StatusCode;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

#[CoversClass(HistoryController::class)]
#[AllowMockObjectsWithoutExpectations]
class HistoryControllerTest extends TestCase
{
    private Authentication&MockObject $authenticationMock;

    private MovieHistoryApi&MockObject $movieHistoryApiMock;

    private MovieApi&MockObject $movieApiMock;

    private MovieReviewApi&MockObject $movieReviewApiMock;

    private HistoryController $subject;

    private UserApi&MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->movieHistoryApiMock = $this->createMock(MovieHistoryApi::class);
        $this->movieApiMock = $this->createMock(MovieApi::class);
        $this->movieReviewApiMock = $this->createMock(MovieReviewApi::class);
        $this->userApiMock = $this->createMock(UserApi::class);

        $this->authenticationMock->method('requireWebSession')->willReturn(AuthenticatedUser::create(12, CredentialType::WEB_SESSION));

        $user = $this->createMock(UserEntity::class);
        $user->method('getId')->willReturn(12);
        $user->method('getName')->willReturn('alice');
        $currentWebUser = $this->createMock(CurrentWebUser::class);
        $currentWebUser->method('requireUser')->willReturn($user);

        $this->subject = new HistoryController(
            $this->createMock(Environment::class),
            $this->movieHistoryApiMock,
            $this->movieApiMock,
            $this->movieReviewApiMock,
            $this->userApiMock,
            $this->createMock(SyncMovie::class),
            $this->authenticationMock,
            $this->createMock(UserPageAuthorizationChecker::class),
            $this->createMock(PaginationElementsCalculator::class),
            $currentWebUser,
        );
    }

    public static function provideHistoryMetadata() : array
    {
        return [
            'set metadata' => ['A comment', 7, 'A comment', 7],
            'clear metadata' => ['', '', null, null],
        ];
    }

    #[DataProvider('provideHistoryMetadata')]
    public function testChangingWatchDateMapsEditRequest(
        string $requestComment,
        int|string $requestLocationId,
        ?string $expectedComment,
        ?int $expectedLocationId,
    ) : void {
        $newDate = Date::createFromString('2026-09-02');
        $play = $this->createStub(MovieHistoryEntity::class);
        $play->method('getMovieId')->willReturn(34);
        $this->movieHistoryApiMock->expects(self::once())->method('findPlayById')->with(9, 12)->willReturn($play);

        $this->movieHistoryApiMock
            ->expects(self::once())
            ->method('updatePlayById')
            ->with(9, 12, $newDate, 3, $expectedComment, $expectedLocationId, true);

        $response = $this->subject->createHistoryEntry($this->createRequest([
            'newWatchDate' => '2026-09-02',
            'playId' => 9,
            'comment' => $requestComment,
            'position' => 3,
            'dateFormat' => 'Y-m-d',
            'locationId' => $requestLocationId,
            'postToMastodon' => true,
        ]));

        self::assertEquals(StatusCode::createNoContent(), $response->getStatusCode());
    }

    public function testLogMovieSavesChangedReview() : void
    {
        $movie = $this->createStub(MovieEntity::class);
        $movie->method('getId')->willReturn(56);
        $this->movieApiMock->expects(self::once())->method('findByTmdbId')->with(78)->willReturn($movie);
        $this->movieReviewApiMock
            ->expects(self::once())
            ->method('save')
            ->with(56, 12, 'A review.', true);

        $response = $this->subject->logMovie($this->createRequest([
            'tmdbId' => 78,
            'watchDate' => '',
            'dateFormat' => 'Y-m-d',
            'personalRating' => 0,
            'comment' => '',
            'locationId' => '',
            'review' => 'A review.',
            'reviewContainsSpoilers' => true,
        ]));

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
    }

    private function createRequest(array $body) : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'alice', 'id' => '34']);
        $request->method('getBody')->willReturn(Json::encode($body));

        return $request;
    }
}
