<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\LocationRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocationRequestMapper::class)]
class LocationRequestMapperTest extends TestCase
{
    private LocationRequestMapper $subject;

    protected function setUp() : void
    {
        $this->subject = new LocationRequestMapper(new RequestMapper(
            $this->createStub(UserApi::class),
            $this->createStub(MovieApi::class),
        ));
    }

    public function testMapsLocation() : void
    {
        self::assertSame(
            ['name' => 'Cinema', 'isCinema' => true],
            $this->subject->mapLocation($this->createRequest('{"name":"Cinema","isCinema":true}')),
        );
    }

    public function testMapsLocationsEnabled() : void
    {
        self::assertFalse($this->subject->mapLocationsEnabled($this->createRequest('{"locationsEnabled":false}')));
    }

    #[DataProvider('invalidLocationProvider')]
    public function testRejectsInvalidLocation(string $body, string $field, string $requirement) : void
    {
        $this->expectExceptionObject(InvalidApiRequest::invalidField($field, $requirement));

        $this->subject->mapLocation($this->createRequest($body));
    }

    public function testRejectsNonBooleanLocationsEnabled() : void
    {
        $this->expectExceptionObject(InvalidApiRequest::invalidField('locationsEnabled', 'must be a boolean'));

        $this->subject->mapLocationsEnabled($this->createRequest('{"locationsEnabled":1}'));
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function invalidLocationProvider() : iterable
    {
        yield 'missing name' => ['{"isCinema":false}', 'name', 'must be a non-empty string'];
        yield 'blank name' => ['{"name":" ","isCinema":false}', 'name', 'must be a non-empty string'];
        yield 'missing cinema flag' => ['{"name":"Home"}', 'isCinema', 'must be a boolean'];
        yield 'invalid cinema flag' => ['{"name":"Home","isCinema":1}', 'isCinema', 'must be a boolean'];
    }

    private function createRequest(string $body) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn($body);

        return $request;
    }
}
