<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\FlashMessage;

use Movary\Service\ApplicationSecret;
use Movary\Service\CookieSecurity;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageCookieCodec;
use Movary\Service\FlashMessage\FlashMessageDestination;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Util\Cookie;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(FlashMessageService::class)]
#[AllowMockObjectsWithoutExpectations]
class FlashMessageServiceTest extends TestCase
{
    private const string APPLICATION_SECRET = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    private FlashMessageCookieCodec $codec;

    private Cookie&MockObject $cookie;

    private bool $cookieSecure = false;

    /** @var array<string, string> */
    private array $cookieValues = [];

    /** @var array<string> */
    private array $deletedCookieNames = [];

    private FlashMessageService $subject;

    protected function setUp() : void
    {
        $this->codec = new FlashMessageCookieCodec(new ApplicationSecret(self::APPLICATION_SECRET));
        $this->cookie = $this->createMock(Cookie::class);
        $this->cookie->method('find')->willReturnCallback(fn(string $name) => $this->cookieValues[$name] ?? null);
        $this->cookie->method('set')->willReturnCallback(function (
            string $name,
            string $value,
            int $expires,
            bool $secure,
        ) : void {
            self::assertSame(1300, $expires);
            $this->cookieSecure = $secure;
            $this->cookieValues[$name] = $value;
        });
        $this->cookie->method('delete')->willReturnCallback(function (string $name, bool $secure) : void {
            $this->cookieSecure = $secure;
            $this->deletedCookieNames[] = $name;
            unset($this->cookieValues[$name]);
        });
        $cookieSecurity = $this->createMock(CookieSecurity::class);
        $cookieSecurity->method('isSecure')->willReturn(true);
        $this->subject = new FlashMessageService(
            $this->codec,
            $this->cookie,
            $cookieSecurity,
            static fn() : int => 1000,
        );
    }

    public function testParallelResponsesWriteSeparateCookiesForSameDestination() : void
    {
        $this->subject->add(FlashMessage::TRAKT_HISTORY_IMPORT_SCHEDULED);
        $this->createSubject()->add(FlashMessage::TRAKT_RATINGS_IMPORT_SCHEDULED);

        self::assertTrue($this->cookieSecure);
        self::assertSame([
            FlashMessageService::COOKIE_NAME_PREFIX . FlashMessage::TRAKT_HISTORY_IMPORT_SCHEDULED->value,
            FlashMessageService::COOKIE_NAME_PREFIX . FlashMessage::TRAKT_RATINGS_IMPORT_SCHEDULED->value,
        ], array_keys($this->cookieValues));
        self::assertTrue(
            $this->codec->isValid(
                FlashMessage::TRAKT_HISTORY_IMPORT_SCHEDULED,
                $this->cookieValues[FlashMessageService::COOKIE_NAME_PREFIX . FlashMessage::TRAKT_HISTORY_IMPORT_SCHEDULED->value],
                1000,
            ),
        );
    }

    public function testNewServiceConsumesDestinationAndPreservesOtherMessages() : void
    {
        $this->subject->add(FlashMessage::PASSWORD_RESET_REQUESTED);
        $this->subject->add(FlashMessage::DASHBOARD_ROWS_RESET);
        $newRequestSubject = $this->createSubject();

        self::assertSame(
            [FlashMessage::PASSWORD_RESET_REQUESTED],
            $newRequestSubject->consumeFor(FlashMessageDestination::PASSWORD_RESET_REQUEST),
        );
        self::assertArrayHasKey(
            FlashMessageService::COOKIE_NAME_PREFIX . FlashMessage::DASHBOARD_ROWS_RESET->value,
            $this->cookieValues,
        );
        self::assertSame([], $newRequestSubject->consumeFor(FlashMessageDestination::PASSWORD_RESET_REQUEST));
    }

    public function testConsumeDeletesMessageCookie() : void
    {
        $this->subject->add(FlashMessage::PASSWORD_RESET_REQUESTED);

        self::assertTrue($this->subject->consume(FlashMessage::PASSWORD_RESET_REQUESTED));
        self::assertSame(
            [FlashMessageService::COOKIE_NAME_PREFIX . FlashMessage::PASSWORD_RESET_REQUESTED->value],
            $this->deletedCookieNames,
        );
    }

    public function testInvalidCookieIsDeletedAndIgnored() : void
    {
        $cookieName = FlashMessageService::COOKIE_NAME_PREFIX . FlashMessage::PASSWORD_RESET_REQUESTED->value;
        $this->cookieValues[$cookieName] = 'invalid-cookie';

        self::assertFalse($this->subject->consume(FlashMessage::PASSWORD_RESET_REQUESTED));
        self::assertSame([$cookieName], $this->deletedCookieNames);
    }

    public function testConsumeReturnsFalseWhenMessageDoesNotExist() : void
    {
        self::assertFalse($this->subject->consume(FlashMessage::PASSWORD_RESET_REQUESTED));
        self::assertSame([], $this->deletedCookieNames);
    }

    public function testClearDeletesCookie() : void
    {
        $this->subject->add(FlashMessage::PASSWORD_RESET_REQUESTED);
        $this->subject->add(FlashMessage::DASHBOARD_ROWS_RESET);

        $this->subject->clear();

        self::assertCount(2, $this->deletedCookieNames);
        self::assertSame([], $this->cookieValues);
        self::assertTrue($this->cookieSecure);
    }

    private function createSubject() : FlashMessageService
    {
        $cookieSecurity = $this->createMock(CookieSecurity::class);
        $cookieSecurity->method('isSecure')->willReturn(true);

        return new FlashMessageService(
            $this->codec,
            $this->cookie,
            $cookieSecurity,
            static fn() : int => 1000,
        );
    }
}
