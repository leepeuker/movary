<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Movary\Service\Email\EmailVerificationEmailRenderer;
use Movary\Service\ServerSettings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Loader\FilesystemLoader;

#[CoversClass(EmailVerificationEmailRenderer::class)]
#[AllowMockObjectsWithoutExpectations]
class EmailVerificationEmailRendererTest extends TestCase
{
    public function testRenderCreatesEscapedVerificationEmail() : void
    {
        $serverSettings = $this->createStub(ServerSettings::class);
        $serverSettings->method('getApplicationName')->willReturn('<Movary & Co>');
        $subject = new EmailVerificationEmailRenderer(
            new FilesystemLoader(dirname(__DIR__, 4) . '/templates'),
            $serverSettings,
        );

        $message = $subject->render(
            'https://movary.example/verify-email?token=abc&source=email',
            24,
        );

        self::assertStringContainsString('Verify your &lt;Movary &amp; Co&gt; email address', $message);
        self::assertStringContainsString('This link expires in 24 hours and can only be used once.', $message);
        self::assertStringContainsString(
            'https://movary.example/verify-email?token=abc&amp;source=email',
            $message,
        );
        self::assertStringNotContainsString('<Movary & Co>', $message);
    }
}
