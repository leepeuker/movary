<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Middleware;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Web\Middleware\ServerHasRegistrationEnabled;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerHasRegistrationEnabled::class)]
#[AllowMockObjectsWithoutExpectations]
class ServerHasRegistrationEnabledTest extends TestCase
{
    #[DataProvider('provideRegistrationAvailability')]
    public function testRegistrationAvailability(
        bool $hasUsers,
        bool $registrationEnabled,
        bool $requestAllowed,
    ) : void {
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('hasUsers')->willReturn($hasUsers);
        $subject = new ServerHasRegistrationEnabled($userApi, $registrationEnabled);

        $response = $subject($this->createStub(Request::class));

        if ($requestAllowed === true) {
            self::assertNull($response);

            return;
        }

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode()->getCode());
    }

    /** @return iterable<string, array{bool, bool, bool}> */
    public static function provideRegistrationAvailability() : iterable
    {
        yield 'initial setup with registration disabled' => [false, false, true];
        yield 'initial setup with registration enabled' => [false, true, true];
        yield 'existing users with registration disabled' => [true, false, false];
        yield 'existing users with registration enabled' => [true, true, true];
    }
}
