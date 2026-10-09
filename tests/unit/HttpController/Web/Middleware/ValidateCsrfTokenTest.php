<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\HttpController\Web\Middleware\ValidateCsrfToken;
use Movary\Service\CsrfTokenProvider;
use Movary\Service\CsrfTokenService;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\HttpController\Web\Middleware\ValidateCsrfToken::class)]
class ValidateCsrfTokenTest extends TestCase
{
    private MockObject&Request $request;

    private MockObject&CsrfTokenService $tokenService;

    private ValidateCsrfToken $subject;

    protected function setUp() : void
    {
        $this->request = $this->createMock(Request::class);
        $this->tokenService = $this->createMock(CsrfTokenService::class);
        $this->subject = new ValidateCsrfToken($this->tokenService);

        $this->request
            ->expects(self::exactly(2))
            ->method('getCookie')
            ->willReturnMap([
                [CsrfTokenProvider::COOKIE_NAME, 'cookie-token'],
                [Authentication::AUTHENTICATION_COOKIE_NAME, 'authentication-token'],
            ]);
    }

    public function testAcceptsHeaderToken() : void
    {
        $this->request->expects(self::once())->method('getHeader')->with('X-CSRF-Token')->willReturn('header-token');
        $this->request->expects(self::never())->method('getPostParameters');
        $this->tokenService
            ->expects(self::once())
            ->method('isValid')
            ->with('cookie-token', 'header-token', 'authentication-token')
            ->willReturn(true);

        self::assertNull(($this->subject)($this->request));
    }

    public function testAcceptsFormToken() : void
    {
        $this->request->expects(self::once())->method('getHeader')->with('X-CSRF-Token')->willReturn(null);
        $this->request->expects(self::once())->method('getPostParameters')->willReturn(['_csrf' => 'form-token']);
        $this->tokenService
            ->expects(self::once())
            ->method('isValid')
            ->with('cookie-token', 'form-token', 'authentication-token')
            ->willReturn(true);

        self::assertNull(($this->subject)($this->request));
    }

    public function testHeaderTakesPrecedenceOverFormToken() : void
    {
        $this->request->expects(self::once())->method('getHeader')->with('X-CSRF-Token')->willReturn('invalid-header');
        $this->request->expects(self::never())->method('getPostParameters');
        $this->tokenService
            ->expects(self::once())
            ->method('isValid')
            ->with('cookie-token', 'invalid-header', 'authentication-token')
            ->willReturn(false);

        $response = ($this->subject)($this->request);

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode()->getCode());
        self::assertNull($response->getBody());
    }

    public function testRejectsMissingToken() : void
    {
        $this->request->expects(self::once())->method('getHeader')->with('X-CSRF-Token')->willReturn(null);
        $this->request->expects(self::once())->method('getPostParameters')->willReturn([]);
        $this->tokenService
            ->expects(self::once())
            ->method('isValid')
            ->with('cookie-token', null, 'authentication-token')
            ->willReturn(false);

        $response = ($this->subject)($this->request);

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode()->getCode());
    }
}
