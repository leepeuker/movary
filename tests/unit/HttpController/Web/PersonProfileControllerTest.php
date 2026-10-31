<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use InvalidArgumentException;
use Movary\Api\Tmdb\Dto\TmdbProfile;
use Movary\Domain\Person\PersonApi;
use Movary\Domain\Person\PersonEntity;
use Movary\Domain\Person\PersonProfileService;
use Movary\HttpController\Web\PersonProfileController;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonProfileController::class)]
#[AllowMockObjectsWithoutExpectations]
class PersonProfileControllerTest extends TestCase
{
    private MockObject|PersonApi $personApi;
    private MockObject|PersonProfileService $profileService;
    private PersonProfileController $subject;

    protected function setUp() : void
    {
        $this->personApi = $this->createMock(PersonApi::class);
        $this->profileService = $this->createMock(PersonProfileService::class);
        $this->subject = new PersonProfileController($this->personApi, $this->profileService);
    }

    public function testFetchProfilesReturnsNotFoundForUnknownPerson() : void
    {
        $this->personApi->expects(self::once())->method('findById')->with(12)->willReturn(null);

        self::assertSame(404, $this->subject->fetchProfiles($this->request())->getStatusCode()->getCode());
    }

    public function testFetchProfilesReturnsAvailableImages() : void
    {
        $person = $this->person('/selected.jpg');
        $this->personApi->method('findById')->willReturn($person);
        $this->profileService->expects(self::once())->method('fetchAvailableProfiles')->with($person)->willReturn([
            new TmdbProfile('/selected.jpg', 'https://example.com/selected.jpg'),
        ]);

        $response = $this->subject->fetchProfiles($this->request());

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame([
            'profiles' => [['path' => '/selected.jpg', 'previewUrl' => 'https://example.com/selected.jpg']],
            'hasOverride' => true,
        ], json_decode((string)$response->getBody(), true));
    }

    public function testSelectProfileReturnsUnprocessableForInvalidJson() : void
    {
        $this->personApi->method('findById')->willReturn($this->person(null));

        self::assertSame(422, $this->subject->selectProfile($this->request('{'))->getStatusCode()->getCode());
    }

    public function testSelectProfileReturnsUnprocessableForUnavailableImage() : void
    {
        $this->personApi->method('findById')->willReturn($this->person(null));
        $this->profileService->method('select')->willThrowException(new InvalidArgumentException());

        self::assertSame(422, $this->subject->selectProfile($this->request('{"profilePath":"/invalid.jpg"}'))->getStatusCode()->getCode());
    }

    public function testSelectProfileDelegatesValidSelection() : void
    {
        $person = $this->person(null);
        $this->personApi->method('findById')->willReturn($person);
        $this->profileService->expects(self::once())->method('select')->with($person, '/selected.jpg');

        self::assertSame(204, $this->subject->selectProfile($this->request('{"profilePath":"/selected.jpg"}'))->getStatusCode()->getCode());
    }

    public function testRestoreDefaultDelegatesReset() : void
    {
        $person = $this->person('/selected.jpg');
        $this->personApi->method('findById')->willReturn($person);
        $this->profileService->expects(self::once())->method('restoreDefault')->with($person);

        self::assertSame(204, $this->subject->restoreDefault($this->request())->getStatusCode()->getCode());
    }

    private function request(string $body = '') : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn(['id' => '12']);
        $request->method('getBody')->willReturn($body);

        return $request;
    }

    private function person(?string $override) : PersonEntity
    {
        return PersonEntity::createFromArray([
            'id' => 12,
            'name' => 'Person',
            'gender' => 0,
            'known_for_department' => null,
            'tmdb_id' => 34,
            'imdb_id' => null,
            'poster_path' => null,
            'tmdb_poster_path' => '/default.jpg',
            'tmdb_poster_path_override' => $override,
            'biography' => null,
            'birth_date' => null,
            'death_date' => null,
            'place_of_birth' => null,
            'updated_at_tmdb' => null,
        ]);
    }
}
