<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Api\Middleware\IsAuthenticated;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsAuthenticated::class)]
class IsAuthenticatedTest extends TestCase
{
    public function testAcceptsPersonalApiToken() : void
    {
        $request = $this->createStub(Request::class);
        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::once())
            ->method('authenticateApiToken')
            ->with($request)
            ->willReturn(AuthenticatedUser::create(12, CredentialType::API_TOKEN));

        self::assertNull((new IsAuthenticated($authentication))($request));
    }

    public function testRejectsMissingOrInvalidPersonalApiToken() : void
    {
        $request = $this->createStub(Request::class);
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('authenticateApiToken')->with($request)->willReturn(null);

        $response = (new IsAuthenticated($authentication))($request);

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode()->getCode());
    }
}
