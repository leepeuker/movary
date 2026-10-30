<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Person\PersonApi;
use Movary\Domain\Person\PersonEntity;
use Movary\HttpController\Api\PersonListController;
use Movary\HttpController\Api\RequestMapper\PersonListRequestMapper;
use Movary\Service\ImageUrlService;
use Movary\Service\PaginationElementsCalculator;
use Movary\ValueObject\Date;
use Movary\ValueObject\Gender;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonListController::class)]
class PersonListControllerTest extends TestCase
{
    public function testReturnsLastPageOfFilteredPersons() : void
    {
        $request = $this->createStub(Request::class);
        $mapper = $this->createStub(PersonListRequestMapper::class);
        $gender = Gender::createFromInt(0);
        $sortOrder = SortOrder::createDesc();
        $mapper->method('mapRequest')->willReturn([
            'page' => 9,
            'limit' => 2,
            'search' => 'Lee',
            'gender' => $gender,
            'role' => 'actor',
            'sortBy' => 'name',
            'sortOrder' => $sortOrder,
        ]);
        $person = $this->createStub(PersonEntity::class);
        $person->method('getId')->willReturn(12);
        $person->method('getTmdbId')->willReturn(34);
        $person->method('getName')->willReturn('Lee');
        $person->method('getGender')->willReturn($gender);
        $person->method('getBirthDate')->willReturn(Date::createFromString('1980-01-02'));
        $person->method('getTmdbPosterPath')->willReturn('/poster.jpg');
        $personApi = $this->createMock(PersonApi::class);
        $personApi->expects(self::once())->method('countAll')->with('Lee', $gender, 'actor')->willReturn(3);
        $personApi->expects(self::once())->method('fetchAllPaginated')
            ->with(2, 2, 'Lee', $gender, 'actor', 'name', $sortOrder)->willReturn([$person]);
        $imageUrlService = $this->createMock(ImageUrlService::class);
        $imageUrlService->expects(self::once())->method('generateImageSrcUrlFromParameters')
            ->with('/poster.jpg', null, 'Lee')->willReturn('https://example.com/poster.jpg');

        $response = (new PersonListController($personApi, $mapper, new PaginationElementsCalculator(), $imageUrlService))
            ->getPersons($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertContainsEquals(Header::createCacheControlNoStore(), $response->getHeaders());
        self::assertSame([
            'persons' => [[
                'id' => 12,
                'tmdbId' => 34,
                'name' => 'Lee',
                'gender' => 0,
                'knownForDepartment' => null,
                'birthDate' => '1980-01-02',
                'posterUrl' => 'https://example.com/poster.jpg',
            ]],
            'currentPage' => 2,
            'maxPage' => 2,
            'totalCount' => 3,
        ], json_decode((string)$response->getBody(), true));
    }
}
