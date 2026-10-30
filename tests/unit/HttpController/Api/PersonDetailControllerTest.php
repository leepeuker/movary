<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Person\PersonApi;
use Movary\Domain\Person\PersonEntity;
use Movary\HttpController\Api\PersonDetailController;
use Movary\Service\ImageUrlService;
use Movary\ValueObject\Date;
use Movary\ValueObject\Gender;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonDetailController::class)]
class PersonDetailControllerTest extends TestCase
{
    public function testReturnsSharedPersonMetadata() : void
    {
        $person = $this->createStub(PersonEntity::class);
        $person->method('getId')->willReturn(12);
        $person->method('getTmdbId')->willReturn(34);
        $person->method('getImdbId')->willReturn('nm1234567');
        $person->method('getName')->willReturn('Person');
        $person->method('getGender')->willReturn(Gender::createFemale());
        $person->method('getKnownForDepartment')->willReturn('Acting');
        $person->method('getBiography')->willReturn('Biography');
        $person->method('getBirthDate')->willReturn(Date::createFromString('1980-01-02'));
        $person->method('getPlaceOfBirth')->willReturn('Berlin');
        $person->method('getTmdbPosterPath')->willReturn('/poster.jpg');
        $personApi = $this->createMock(PersonApi::class);
        $personApi->expects(self::once())->method('findById')->with(12)->willReturn($person);
        $imageUrlService = $this->createMock(ImageUrlService::class);
        $imageUrlService->expects(self::once())->method('generateImageSrcUrlFromParameters')
            ->with('/poster.jpg', null, 'Person')->willReturn('https://example.com/poster.jpg');

        $response = (new PersonDetailController($personApi, $imageUrlService))->getPerson($this->request());

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame([
            'id' => 12,
            'tmdbId' => 34,
            'imdbId' => 'nm1234567',
            'name' => 'Person',
            'gender' => 1,
            'knownForDepartment' => 'Acting',
            'biography' => 'Biography',
            'birthDate' => '1980-01-02',
            'deathDate' => null,
            'placeOfBirth' => 'Berlin',
            'posterUrl' => 'https://example.com/poster.jpg',
        ], json_decode((string)$response->getBody(), true));
    }

    public function testReturnsNotFoundForUnknownPerson() : void
    {
        $personApi = $this->createMock(PersonApi::class);
        $personApi->expects(self::once())->method('findById')->with(12)->willReturn(null);
        $imageUrlService = $this->createMock(ImageUrlService::class);
        $imageUrlService->expects(self::never())->method('generateImageSrcUrlFromParameters');

        $response = (new PersonDetailController($personApi, $imageUrlService))->getPerson($this->request());

        self::assertSame(404, $response->getStatusCode()->getCode());
    }

    private function request() : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['personId' => '12']);

        return $request;
    }
}
