<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use InvalidArgumentException;
use Movary\Api\Tmdb\Dto\TmdbProfile;
use Movary\Domain\Person\PersonApi;
use Movary\Domain\Person\PersonEntity;
use Movary\Domain\Person\PersonProfileService;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\PersonProfileController;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Movary\Domain\Movie\MovieApi;

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
        $this->subject = new PersonProfileController(
            $this->personApi,
            $this->profileService,
            new RequestMapper($this->createStub(UserApi::class), $this->createStub(MovieApi::class)),
        );
    }

    public function testFetchProfilesReturnsNotFoundForUnknownPerson() : void
    {
        $this->personApi->expects(self::once())->method('findById')->with(12)->willReturn(null);
        $this->profileService->expects(self::never())->method('fetchAvailableProfiles');

        self::assertSame(404, $this->subject->fetchProfiles($this->request())->getStatusCode()->getCode());
    }

    public function testFetchProfilesReturnsAvailableProfilesAndOverrideState() : void
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

    public function testSelectProfileRejectsMissingPath() : void
    {
        $this->personApi->method('findById')->willReturn($this->person(null));

        $this->expectExceptionObject(InvalidApiRequest::invalidField('profilePath', 'is required and must be a non-empty string'));
        $this->subject->selectProfile($this->request('{}'));
    }

    public function testSelectProfileRejectsUnavailablePath() : void
    {
        $person = $this->person(null);
        $this->personApi->method('findById')->willReturn($person);
        $this->profileService->method('select')->willThrowException(new InvalidArgumentException());

        $this->expectExceptionObject(InvalidApiRequest::invalidField('profilePath', 'must identify an available TMDB profile'));
        $this->subject->selectProfile($this->request('{"profilePath":"/unknown.jpg"}'));
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
        $request->method('getRouteParameters')->willReturn(['personId' => '12']);
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
