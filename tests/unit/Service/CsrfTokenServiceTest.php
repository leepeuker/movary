<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Service\ApplicationSecret;
use Movary\Service\CsrfTokenService;
use Movary\ValueObject\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\CsrfTokenService::class)]
class CsrfTokenServiceTest extends TestCase
{
    private const string APPLICATION_SECRET = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    private CsrfTokenService $subject;

    protected function setUp() : void
    {
        $this->subject = $this->createService(self::APPLICATION_SECRET);
    }

    public function testCreatesValidAuthenticatedToken() : void
    {
        $token = $this->subject->create('authentication-token');

        self::assertMatchesRegularExpression('/\Av1\.[0-9a-f]{64}\.[0-9a-f]{64}\z/D', $token);
        self::assertTrue($this->subject->isValid($token, $token, 'authentication-token'));
    }

    public function testCreatesValidAnonymousToken() : void
    {
        $token = $this->subject->create(null);

        self::assertTrue($this->subject->isValid($token, $token, null));
    }

    public function testRejectsMissingTokens() : void
    {
        $token = $this->subject->create(null);

        self::assertFalse($this->subject->isValid(null, $token, null));
        self::assertFalse($this->subject->isValid($token, null, null));
    }

    public function testRejectsMalformedTruncatedAndOversizedTokens() : void
    {
        $token = $this->subject->create(null);

        self::assertFalse($this->subject->isValid(str_repeat('a', 132), str_repeat('a', 132), null));
        self::assertFalse($this->subject->isValid(substr($token, 0, -1), substr($token, 0, -1), null));
        self::assertFalse($this->subject->isValid($token . 'a', $token . 'a', null));
    }

    public function testRejectsCookieAndSubmittedTokenMismatch() : void
    {
        $cookieToken = $this->subject->create(null);
        $submittedToken = $this->subject->create(null);

        self::assertFalse($this->subject->isValid($cookieToken, $submittedToken, null));
    }

    public function testRejectsInvalidSignature() : void
    {
        $token = $this->subject->create(null);
        $tokenWithInvalidSignature = substr($token, 0, -1) . ($token[-1] === '0' ? '1' : '0');

        self::assertFalse($this->subject->isValid($tokenWithInvalidSignature, $tokenWithInvalidSignature, null));
    }

    public function testRejectsAuthenticationTokenReplacement() : void
    {
        $token = $this->subject->create('first-authentication-token');

        self::assertFalse($this->subject->isValid($token, $token, 'second-authentication-token'));
    }

    public function testRejectsApplicationSecretReplacement() : void
    {
        $token = $this->subject->create(null);
        $serviceWithDifferentSecret = $this->createService(str_repeat('a', 64));

        self::assertFalse($serviceWithDifferentSecret->isValid($token, $token, null));
    }

    public function testRejectsUnsupportedTokenVersion() : void
    {
        $token = $this->subject->create(null);
        $tokenWithUnsupportedVersion = 'v2' . substr($token, 2);

        self::assertFalse($this->subject->isValid($tokenWithUnsupportedVersion, $tokenWithUnsupportedVersion, null));
    }

    private function createService(string $applicationSecret) : CsrfTokenService
    {
        $config = $this->createMock(Config::class);
        $config
            ->expects(self::once())
            ->method('getAsString')
            ->with('APPLICATION_SECRET')
            ->willReturn($applicationSecret);

        return new CsrfTokenService(new ApplicationSecret($config));
    }
}
