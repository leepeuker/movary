<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\RequestMapper\WatchlistRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WatchlistRequestMapper::class)]
class WatchlistRequestMapperTest extends TestCase
{
    public function testMapsExistingMovieAdditions() : void
    {
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi
            ->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn($this->createStub(MovieEntity::class));

        self::assertSame([42], $this->createSubject($movieApi)->mapAdditions(
            $this->createRequest('[{"movaryId":42}]'),
        ));
    }

    public function testRejectsUnknownMovieAddition() : void
    {
        $movieApi = $this->createStub(MovieApi::class);
        $this->expectExceptionObject(InvalidApiRequest::movieNotFound(42));

        $this->createSubject($movieApi)->mapAdditions($this->createRequest('[{"movaryId":42}]'));
    }

    public function testMapsPositiveMovieDeletionsWithoutRequiringExistingMovie() : void
    {
        self::assertSame(
            [42],
            $this->createSubject($this->createStub(MovieApi::class))->mapDeletions(
                $this->createRequest('[{"movaryId":42}]'),
            ),
        );
    }

    public function testRejectsMissingMovieId() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('items[0].movaryId', 'must be a positive integer'),
        );

        $this->createSubject($this->createStub(MovieApi::class))->mapDeletions($this->createRequest('[{}]'));
    }

    private function createSubject(MovieApi $movieApi) : WatchlistRequestMapper
    {
        return new WatchlistRequestMapper(new RequestMapper(
            $this->createStub(UserApi::class),
            $movieApi,
        ));
    }

    private function createRequest(string $body) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn($body);

        return $request;
    }
}
