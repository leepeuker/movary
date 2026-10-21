<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\MovieAddRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieAddRequestMapper::class)]
class MovieAddRequestMapperTest extends TestCase
{
    private MovieAddRequestMapper $subject;

    protected function setUp() : void
    {
        $this->subject = new MovieAddRequestMapper(new RequestMapper(
            $this->createStub(UserApi::class),
            $this->createStub(MovieApi::class),
        ));
    }

    public function testMapsTmdbId() : void
    {
        self::assertSame(42, $this->subject->mapRequest($this->createRequest('{"tmdbId":42}'))->getTmdbId());
    }

    public function testRejectsMissingTmdbId() : void
    {
        $this->expectExceptionObject(InvalidApiRequest::missingProviderId());

        $this->subject->mapRequest($this->createRequest('{}'));
    }

    #[DataProvider('invalidTmdbIdProvider')]
    public function testRejectsInvalidTmdbId(string $body) : void
    {
        $this->expectExceptionObject(InvalidApiRequest::invalidField('tmdbId', 'must be a positive integer'));

        $this->subject->mapRequest($this->createRequest($body));
    }

    public static function invalidTmdbIdProvider() : array
    {
        return [
            'null' => ['{"tmdbId":null}'],
            'string' => ['{"tmdbId":"42"}'],
            'zero' => ['{"tmdbId":0}'],
            'negative' => ['{"tmdbId":-1}'],
            'array' => ['{"tmdbId":[42]}'],
        ];
    }

    private function createRequest(string $body) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn($body);

        return $request;
    }
}
