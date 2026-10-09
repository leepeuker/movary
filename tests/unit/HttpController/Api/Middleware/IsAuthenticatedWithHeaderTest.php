<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\HttpController\Api\Middleware\IsAuthenticatedWithHeader;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\HttpController\Api\Middleware\IsAuthenticatedWithHeader::class)]
class IsAuthenticatedWithHeaderTest extends TestCase
{
    public function testAcceptsExplicitHeaderToken() : void
    {
        $request = $this->createStub(Request::class);
        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::once())
            ->method('getUserIdByTokenFromHeader')
            ->with($request)
            ->willReturn(12);

        self::assertNull((new IsAuthenticatedWithHeader($authentication))($request));
    }

    public function testRejectsMissingOrInvalidHeaderToken() : void
    {
        $request = $this->createStub(Request::class);
        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::once())
            ->method('getUserIdByTokenFromHeader')
            ->with($request)
            ->willReturn(null);

        $response = (new IsAuthenticatedWithHeader($authentication))($request);

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode()->getCode());
    }
}
