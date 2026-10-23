<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\MovieReviewRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieReviewRequestMapper::class)]
class MovieReviewRequestMapperTest extends TestCase
{
    private MovieReviewRequestMapper $subject;

    protected function setUp() : void
    {
        $this->subject = new MovieReviewRequestMapper(new RequestMapper(
            $this->createStub(UserApi::class),
            $this->createStub(MovieApi::class),
        ));
    }

    public function testMapsReview() : void
    {
        self::assertSame(
            ['review' => 'A review', 'containsSpoilers' => true],
            $this->subject->mapReview($this->createRequest('{"review":"A review","containsSpoilers":true}')),
        );
    }

    public function testDefaultsContainsSpoilersToFalse() : void
    {
        self::assertSame(
            ['review' => 'A review', 'containsSpoilers' => false],
            $this->subject->mapReview($this->createRequest('{"review":"A review"}')),
        );
    }

    #[DataProvider('invalidReviewProvider')]
    public function testRejectsInvalidReview(string $body) : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('review', 'is required and must be a non-empty string'),
        );

        $this->subject->mapReview($this->createRequest($body));
    }

    public function testRejectsReviewAboveCharacterLimit() : void
    {
        $request = $this->createRequest(json_encode(['review' => str_repeat('ü', 10_001)], JSON_THROW_ON_ERROR));
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('review', 'must not exceed 10000 characters'),
        );

        $this->subject->mapReview($request);
    }

    public function testRejectsInvalidContainsSpoilers() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('containsSpoilers', 'must be a boolean'),
        );

        $this->subject->mapReview($this->createRequest('{"review":"A review","containsSpoilers":1}'));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidReviewProvider() : iterable
    {
        yield 'missing' => ['{}'];
        yield 'null' => ['{"review":null}'];
        yield 'not a string' => ['{"review":123}'];
        yield 'empty' => ['{"review":""}'];
        yield 'blank' => ['{"review":"  "}'];
    }

    private function createRequest(string $body) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn($body);

        return $request;
    }
}
