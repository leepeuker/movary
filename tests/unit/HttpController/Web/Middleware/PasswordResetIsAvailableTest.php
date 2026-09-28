<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Middleware;

use Movary\HttpController\Web\Middleware\PasswordResetIsAvailable;
use Movary\Service\Email\EmailSupport;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordResetIsAvailable::class)]
#[AllowMockObjectsWithoutExpectations]
class PasswordResetIsAvailableTest extends TestCase
{
    private EmailSupport|MockObject $emailSupportMock;

    private PasswordResetIsAvailable $subject;

    protected function setUp() : void
    {
        $this->emailSupportMock = $this->createMock(EmailSupport::class);
        $this->subject = new PasswordResetIsAvailable($this->emailSupportMock);
    }

    public function testRequestContinuesWhenPasswordResetIsAvailable() : void
    {
        $this->emailSupportMock->method('isPasswordResetAvailable')->willReturn(true);

        self::assertNull(($this->subject)($this->createMock(Request::class)));
    }

    public function testRequestReturnsNotFoundWhenPasswordResetIsUnavailable() : void
    {
        $this->emailSupportMock->method('isPasswordResetAvailable')->willReturn(false);

        $response = ($this->subject)($this->createMock(Request::class));

        self::assertNotNull($response);
        self::assertSame(404, $response->getStatusCode()->getCode());
    }
}
