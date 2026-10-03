<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Api\PlayedController;
use Movary\HttpController\Api\RequestMapper\PlayedRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\PlayedResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\StatusCode;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlayedController::class)]
#[AllowMockObjectsWithoutExpectations]
class PlayedControllerTest extends TestCase
{
    private MovieApi&MockObject $movieApiMock;

    private PlayedController $subject;

    protected function setUp() : void
    {
        $this->movieApiMock = $this->createMock(MovieApi::class);

        $user = $this->createMock(UserEntity::class);
        $user->method('getId')->willReturn(12);

        $requestMapper = $this->createMock(RequestMapper::class);
        $requestMapper->method('mapUsernameFromRoute')->willReturn($user);

        $this->subject = new PlayedController(
            $this->movieApiMock,
            $this->createMock(PaginationElementsCalculator::class),
            $this->createMock(PlayedRequestMapper::class),
            $this->createMock(PlayedResponseMapper::class),
            $requestMapper,
        );
    }

    public function testAddToPlayedMapsCommentToCommentParameter() : void
    {
        $watchDate = Date::createFromString('2026-09-01');
        $this->movieApiMock
            ->expects(self::once())
            ->method('addPlaysForMovieOnDate')
            ->with(34, 12, $watchDate, 2, null, 'A comment');

        $response = $this->subject->addToPlayed($this->createRequest('A comment'));

        self::assertSame(StatusCode::createNoContent()->getCode(), $response->getStatusCode()->getCode());
    }

    public function testUpdatePlayedMapsCommentToCommentParameter() : void
    {
        $watchDate = Date::createFromString('2026-09-01');
        $this->movieApiMock
            ->expects(self::once())
            ->method('replaceHistoryForMovieByDate')
            ->with(34, 12, $watchDate, 2, null, 'A comment');

        $response = $this->subject->updatePlayed($this->createRequest('A comment'));

        self::assertSame(StatusCode::createNoContent()->getCode(), $response->getStatusCode()->getCode());
    }

    private function createRequest(string $comment) : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            [
                'movaryId' => 34,
                'watchDates' => [
                    [
                        'watchedAt' => '2026-09-01',
                        'plays' => 2,
                        'comment' => $comment,
                    ],
                ],
            ],
        ]));

        return $request;
    }
}
