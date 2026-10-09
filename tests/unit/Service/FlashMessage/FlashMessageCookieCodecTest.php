<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\FlashMessage;

use Movary\Service\ApplicationSecret;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageCookieCodec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FlashMessageCookieCodec::class)]
class FlashMessageCookieCodecTest extends TestCase
{
    private const string APPLICATION_SECRET = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    private FlashMessageCookieCodec $subject;

    protected function setUp() : void
    {
        $this->subject = new FlashMessageCookieCodec(new ApplicationSecret(self::APPLICATION_SECRET));
    }

    public function testEncodedMessageIsValid() : void
    {
        $cookie = $this->subject->encode(FlashMessage::PASSWORD_RESET_REQUESTED, 1300);

        self::assertTrue($this->subject->isValid(FlashMessage::PASSWORD_RESET_REQUESTED, $cookie, 1000));
    }

    public function testValidationRejectsExpiredCookieAtBoundary() : void
    {
        $cookie = $this->subject->encode(FlashMessage::PASSWORD_RESET_REQUESTED, 1000);

        self::assertFalse($this->subject->isValid(FlashMessage::PASSWORD_RESET_REQUESTED, $cookie, 1000));
    }

    public function testValidationRejectsCookieForDifferentMessage() : void
    {
        $cookie = $this->subject->encode(FlashMessage::PASSWORD_RESET_REQUESTED, 1300);

        self::assertFalse($this->subject->isValid(FlashMessage::DASHBOARD_ROWS_RESET, $cookie, 1000));
    }

    public function testValidationRejectsChangedSignature() : void
    {
        $cookie = $this->subject->encode(FlashMessage::PASSWORD_RESET_REQUESTED, 1300);
        $signatureOffset = (int)strrpos($cookie, '.') + 1;
        $cookie[$signatureOffset] = $cookie[$signatureOffset] === 'a' ? 'b' : 'a';

        self::assertFalse($this->subject->isValid(FlashMessage::PASSWORD_RESET_REQUESTED, $cookie, 1000));
    }

    public function testValidationRejectsMalformedCookie() : void
    {
        self::assertFalse($this->subject->isValid(FlashMessage::PASSWORD_RESET_REQUESTED, 'not-a-cookie', 1000));
        self::assertFalse($this->subject->isValid(FlashMessage::PASSWORD_RESET_REQUESTED, 'v2.1300.' . str_repeat('a', 64), 1000));
        self::assertFalse($this->subject->isValid(FlashMessage::PASSWORD_RESET_REQUESTED, str_repeat('a', 81), 1000));
    }
}
