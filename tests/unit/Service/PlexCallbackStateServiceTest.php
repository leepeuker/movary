<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Service\ApplicationSecret;
use Movary\Service\PlexCallbackStateService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\PlexCallbackStateService::class)]
class PlexCallbackStateServiceTest extends TestCase
{
    private ApplicationSecret $applicationSecret;

    protected function setUp() : void
    {
        $this->applicationSecret = new ApplicationSecret(str_repeat('a', 64));
    }

    public function testAcceptsMatchingUnexpiredState() : void
    {
        $subject = new PlexCallbackStateService($this->applicationSecret, 1000);
        $state = $subject->create('pin-id', 'plex-code', 'authentication-token');

        self::assertTrue($subject->isValid($state, 'pin-id', 'plex-code', 'authentication-token'));
    }

    public function testRejectsMissingAndMalformedState() : void
    {
        $subject = new PlexCallbackStateService($this->applicationSecret, 1000);

        self::assertFalse($subject->isValid(null, 'pin-id', 'plex-code', 'authentication-token'));
        self::assertFalse($subject->isValid('invalid', 'pin-id', 'plex-code', 'authentication-token'));
        self::assertFalse(
            $subject->isValid(str_repeat('a', 129), 'pin-id', 'plex-code', 'authentication-token'),
        );
    }

    public function testRejectsExpiredState() : void
    {
        $state = (new PlexCallbackStateService($this->applicationSecret, 1000))->create(
            'pin-id',
            'plex-code',
            'authentication-token',
        );
        $subject = new PlexCallbackStateService($this->applicationSecret, 1900);

        self::assertFalse($subject->isValid($state, 'pin-id', 'plex-code', 'authentication-token'));
    }

    public function testRejectsMismatchedContext() : void
    {
        $subject = new PlexCallbackStateService($this->applicationSecret, 1000);
        $state = $subject->create('pin-id', 'plex-code', 'authentication-token');

        self::assertFalse($subject->isValid($state, 'different-pin', 'plex-code', 'authentication-token'));
        self::assertFalse($subject->isValid($state, 'pin-id', 'different-code', 'authentication-token'));
        self::assertFalse($subject->isValid($state, 'pin-id', 'plex-code', 'different-token'));
        self::assertFalse($subject->isValid($state, 'pin-id', 'plex-code', null));
    }

    public function testRejectsUnsupportedVersion() : void
    {
        $subject = new PlexCallbackStateService($this->applicationSecret, 1000);
        $state = $subject->create('pin-id', 'plex-code', 'authentication-token');
        $unsupportedState = 'v2' . substr($state, 2);

        self::assertFalse(
            $subject->isValid($unsupportedState, 'pin-id', 'plex-code', 'authentication-token'),
        );
    }
}
