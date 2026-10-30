<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\MovieRatingRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieRatingRequestMapper::class)]
class MovieRatingRequestMapperTest extends TestCase
{
    private MovieRatingRequestMapper $subject;

    protected function setUp() : void
    {
        $this->subject = new MovieRatingRequestMapper(new RequestMapper(
            $this->createStub(UserApi::class),
            $this->createStub(MovieApi::class),
        ));
    }

    public function testMapsRating() : void
    {
        self::assertSame(8, $this->subject->mapRating($this->createRequest('{"rating":8}'))->asInt());
    }

    #[DataProvider('invalidRatingProvider')]
    public function testRejectsInvalidRating(string $body) : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('rating', 'must be an integer between 1 and 10'),
        );

        $this->subject->mapRating($this->createRequest($body));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidRatingProvider() : iterable
    {
        yield 'missing' => ['{}'];
        yield 'zero' => ['{"rating":0}'];
        yield 'eleven' => ['{"rating":11}'];
        yield 'string' => ['{"rating":"8"}'];
        yield 'decimal' => ['{"rating":8.5}'];
    }

    private function createRequest(string $body) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn($body);

        return $request;
    }
}
