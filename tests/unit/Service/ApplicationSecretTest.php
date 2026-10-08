<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use InvalidArgumentException;
use Movary\Service\ApplicationSecret;
use Movary\ValueObject\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\ApplicationSecret::class)]
class ApplicationSecretTest extends TestCase
{
    public function testDerivesDifferentKeysForDifferentPurposes() : void
    {
        $subject = new ApplicationSecret($this->createConfig(str_repeat('a', 64)));

        self::assertNotSame($subject->deriveKey('csrf'), $subject->deriveKey('plex-callback'));
        self::assertSame(32, strlen($subject->deriveKey('csrf')));
    }

    public function testRejectsInvalidApplicationSecret() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'APPLICATION_SECRET must contain exactly 64 hexadecimal characters.',
        );

        new ApplicationSecret($this->createConfig('too-short'));
    }

    private function createConfig(string $applicationSecret) : Config
    {
        $config = $this->createMock(Config::class);
        $config
            ->expects(self::once())
            ->method('getAsString')
            ->with('APPLICATION_SECRET')
            ->willReturn($applicationSecret);

        return $config;
    }
}
