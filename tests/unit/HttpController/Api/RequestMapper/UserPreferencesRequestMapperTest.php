<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Country\CountryApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\RequestMapper\UserPreferencesRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserPreferencesRequestMapper::class)]
class UserPreferencesRequestMapperTest extends TestCase
{
    public function testMapsGeneralPreferences() : void
    {
        $data = [
            'username' => 'NewName',
            'privacyLevel' => 2,
            'dateFormatId' => 3,
            'movieTitleDisplay' => 'both',
            'country' => 'DE',
            'watchlistAutomaticRemovalEnabled' => true,
            'displayCharacterNames' => false,
            'displayTmdbRatings' => true,
            'displayImdbRatings' => false,
        ];

        self::assertSame($data, $this->createSubject($data)->mapUpdate($this->createStub(Request::class)));
    }

    public function testAllowsPartialUpdateAndNullCountry() : void
    {
        self::assertSame(
            ['country' => null],
            $this->createSubject(['country' => null])->mapUpdate($this->createStub(Request::class)),
        );
    }

    #[DataProvider('invalidFieldProvider')]
    public function testRejectsInvalidField(array $data, string $field) : void
    {
        $this->expectException(InvalidApiRequest::class);
        $this->expectExceptionMessage('Field "' . $field . '"');

        $this->createSubject($data)->mapUpdate($this->createStub(Request::class));
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidFieldProvider() : iterable
    {
        yield 'empty' => [[], 'preferences'];
        yield 'unknown' => [['unknown' => 1], 'unknown'];
        yield 'username type' => [['username' => 1], 'username'];
        yield 'privacy level' => [['privacyLevel' => 3], 'privacyLevel'];
        yield 'date format' => [['dateFormatId' => '3'], 'dateFormatId'];
        yield 'title display' => [['movieTitleDisplay' => 'invalid'], 'movieTitleDisplay'];
        yield 'country' => [['country' => 'XX'], 'country'];
        yield 'boolean' => [['displayImdbRatings' => 1], 'displayImdbRatings'];
    }

    private function createSubject(array $data) : UserPreferencesRequestMapper
    {
        $requestMapper = $this->createStub(RequestMapper::class);
        $requestMapper->method('mapJsonObject')->willReturn($data);
        $countryApi = $this->createStub(CountryApi::class);
        $countryApi->method('getIso31661ToNameMap')->willReturn(['DE' => 'Germany']);

        return new UserPreferencesRequestMapper($requestMapper, $countryApi);
    }
}
