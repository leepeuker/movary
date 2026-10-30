<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationEntity;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationEntityList;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Api\LocationController;
use Movary\HttpController\Api\RequestMapper\LocationRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocationController::class)]
#[AllowMockObjectsWithoutExpectations]
class LocationControllerTest extends TestCase
{
    private MovieHistoryLocationApi&MockObject $locationApi;
    private UserApi&MockObject $userApi;
    private LocationRequestMapper&MockObject $locationRequestMapper;
    private LocationController $subject;

    protected function setUp() : void
    {
        $this->locationApi = $this->createMock(MovieHistoryLocationApi::class);
        $this->userApi = $this->createMock(UserApi::class);
        $this->locationRequestMapper = $this->createMock(LocationRequestMapper::class);
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(34);
        $requestMapper = $this->createStub(RequestMapper::class);
        $requestMapper->method('mapUsernameFromRoute')->willReturn($user);
        $this->subject = new LocationController(
            $this->locationApi,
            $this->userApi,
            $this->locationRequestMapper,
            $requestMapper,
        );
    }

    public function testListReturnsOnlyRequestedUsersLocationsWithoutCaching() : void
    {
        $this->locationApi->expects(self::once())->method('findLocationsByUserId')->with(34)->willReturn(
            MovieHistoryLocationEntityList::createFromArray([$this->locationData(34)]),
        );

        $response = $this->subject->getLocations($this->createRequest());

        self::assertSame('{"locations":[{"id":12,"name":"Home","isCinema":false}]}', $response->getBody());
        self::assertContainsEquals(Header::createCacheControlNoStore(), $response->getHeaders());
    }

    public function testCreateDelegatesValidatedLocation() : void
    {
        $request = $this->createRequest();
        $this->locationRequestMapper->expects(self::once())->method('mapLocation')->with($request)->willReturn(
            ['name' => 'Cinema', 'isCinema' => true],
        );
        $this->locationApi->expects(self::once())->method('createLocation')->with(34, 'Cinema', true);

        self::assertSame(204, $this->subject->createLocation($request)->getStatusCode()->getCode());
    }

    public function testOtherUsersLocationIsNotReadableOrEditable() : void
    {
        $this->locationApi->expects(self::exactly(2))->method('findLocationById')->with(12)->willReturn(
            MovieHistoryLocationEntity::createFromArray($this->locationData(99)),
        );
        $this->locationApi->expects(self::never())->method('updateLocation');

        self::assertSame(404, $this->subject->getLocation($this->createRequest())->getStatusCode()->getCode());
        self::assertSame(404, $this->subject->updateLocation($this->createRequest())->getStatusCode()->getCode());
    }

    public function testDeleteMissingLocationIsIdempotent() : void
    {
        $this->locationApi->method('findLocationById')->willReturn(null);
        $this->locationApi->expects(self::never())->method('deleteLocation');

        self::assertSame(204, $this->subject->deleteLocation($this->createRequest())->getStatusCode()->getCode());
    }

    public function testUpdatesSettingsForRequestedUser() : void
    {
        $request = $this->createRequest();
        $this->locationRequestMapper->expects(self::once())->method('mapLocationsEnabled')->with($request)->willReturn(false);
        $this->userApi->expects(self::once())->method('updateLocationsEnabled')->with(34, false);

        self::assertSame(204, $this->subject->updateSettings($request)->getStatusCode()->getCode());
    }

    private function createRequest() : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'User', 'locationId' => '12']);

        return $request;
    }

    /** @return array{id: int, user_id: int, name: string, is_cinema: int} */
    private function locationData(int $userId) : array
    {
        return ['id' => 12, 'user_id' => $userId, 'name' => 'Home', 'is_cinema' => 0];
    }
}
