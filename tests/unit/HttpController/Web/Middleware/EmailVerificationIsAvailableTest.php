<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Middleware;

use Movary\HttpController\Web\Middleware\EmailVerificationIsAvailable;
use Movary\Service\Email\EmailSupport;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailVerificationIsAvailable::class)]
#[AllowMockObjectsWithoutExpectations]
class EmailVerificationIsAvailableTest extends TestCase
{
    public function testRequestContinuesWhenVerificationIsAvailable() : void
    {
        $emailSupport = $this->createStub(EmailSupport::class);
        $emailSupport->method('isEmailVerificationAvailable')->willReturn(true);

        self::assertNull((new EmailVerificationIsAvailable($emailSupport))($this->createStub(Request::class)));
    }

    public function testRequestReturnsNotFoundWhenVerificationIsUnavailable() : void
    {
        $emailSupport = $this->createStub(EmailSupport::class);
        $emailSupport->method('isEmailVerificationAvailable')->willReturn(false);

        $response = (new EmailVerificationIsAvailable($emailSupport))($this->createStub(Request::class));

        self::assertNotNull($response);
        self::assertSame(404, $response->getStatusCode()->getCode());
    }
}
