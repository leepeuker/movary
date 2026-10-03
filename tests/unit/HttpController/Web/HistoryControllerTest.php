<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\History\MovieHistoryEditor;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\Authentication;
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

    private MovieHistoryEditor&MockObject $movieHistoryEditorMock;

    private MovieApi&MockObject $movieApiMock;

    private HistoryController $subject;

    private UserApi&MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->movieHistoryEditorMock = $this->createMock(MovieHistoryEditor::class);
        $this->movieApiMock = $this->createMock(MovieApi::class);
        $this->userApiMock = $this->createMock(UserApi::class);

        $this->authenticationMock->method('getCurrentUserId')->willReturn(12);

        $user = $this->createMock(UserEntity::class);
        $user->method('getName')->willReturn('alice');
        $this->userApiMock->expects(self::once())->method('fetchUser')->with(12)->willReturn($user);

        $this->subject = new HistoryController(
            $this->createMock(Environment::class),
            $this->createMock(MovieHistoryApi::class),
            $this->movieHistoryEditorMock,
            $this->movieApiMock,
            $this->userApiMock,
            $this->createMock(SyncMovie::class),
            $this->authenticationMock,
            $this->createMock(UserPageAuthorizationChecker::class),
            $this->createMock(PaginationElementsCalculator::class),
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
        $originalDate = Date::createFromString('2026-09-01');
        $newDate = Date::createFromString('2026-09-02');

        $this->movieHistoryEditorMock
            ->expects(self::once())
            ->method('update')
            ->with(34, 12, $originalDate, $newDate, 2, 3, $expectedComment, $expectedLocationId, true);

        $response = $this->subject->createHistoryEntry($this->createRequest([
            'newWatchDate' => '2026-09-02',
            'originalWatchDate' => '2026-09-01',
            'plays' => 2,
            'comment' => $requestComment,
            'position' => 3,
            'dateFormat' => 'Y-m-d',
            'locationId' => $requestLocationId,
            'postToMastodon' => true,
        ]));

        self::assertEquals(StatusCode::createNoContent(), $response->getStatusCode());
    }

    private function createRequest(array $body) : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'alice', 'id' => '34']);
        $request->method('getBody')->willReturn(Json::encode($body));

        return $request;
    }
}
