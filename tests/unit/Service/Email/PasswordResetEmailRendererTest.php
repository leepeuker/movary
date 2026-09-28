<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Movary\Service\Email\PasswordResetEmailRenderer;
use Movary\Service\ServerSettings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Loader\FilesystemLoader;

#[CoversClass(PasswordResetEmailRenderer::class)]
#[AllowMockObjectsWithoutExpectations]
class PasswordResetEmailRendererTest extends TestCase
{
    private ServerSettings|MockObject $serverSettingsMock;

    private PasswordResetEmailRenderer $subject;

    protected function setUp() : void
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 4) . '/templates');
        $this->serverSettingsMock = $this->createMock(ServerSettings::class);
        $this->subject = new PasswordResetEmailRenderer($loader, $this->serverSettingsMock);
    }

    public function testRenderCreatesEscapedPasswordResetEmail() : void
    {
        $this->serverSettingsMock->method('getApplicationName')->willReturn('<Movary & Co>');

        $message = $this->subject->render(
            'https://movary.example/reset-password?token=abc&source=email',
            15,
        );

        self::assertStringContainsString('Reset your &lt;Movary &amp; Co&gt; password', $message);
        self::assertStringContainsString('This link expires in 15 minutes and can only be used once.', $message);
        self::assertStringContainsString(
            'https://movary.example/reset-password?token=abc&amp;source=email',
            $message,
        );
        self::assertStringNotContainsString('<Movary & Co>', $message);
        self::assertStringNotContainsString('token=abc&source=email', $message);
    }

    public function testRenderUsesDefaultApplicationName() : void
    {
        $this->serverSettingsMock->method('getApplicationName')->willReturn(null);

        self::assertStringContainsString(
            'Reset your Movary password',
            $this->subject->render('https://movary.example/reset-password?token=abc', 15),
        );
    }
}
