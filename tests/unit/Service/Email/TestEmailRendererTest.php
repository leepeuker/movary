<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Movary\Service\Email\TestEmailRenderer;
use Movary\Service\ServerSettings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Loader\FilesystemLoader;

#[CoversClass(TestEmailRenderer::class)]
#[AllowMockObjectsWithoutExpectations]
class TestEmailRendererTest extends TestCase
{
    private ServerSettings|MockObject $serverSettingsMock;

    private TestEmailRenderer $subject;

    protected function setUp() : void
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 4) . '/templates');
        $this->serverSettingsMock = $this->createMock(ServerSettings::class);
        $this->subject = new TestEmailRenderer($loader, $this->serverSettingsMock);
    }

    public function testRenderCreatesEscapedTestEmail() : void
    {
        $this->serverSettingsMock->method('getApplicationName')->willReturn('<Movary & Co>');

        $message = $this->subject->render();

        self::assertStringContainsString('Test email from &lt;Movary &amp; Co&gt;', $message);
        self::assertStringContainsString(
            'This test email confirms that your current email settings are working.',
            $message,
        );
        self::assertStringNotContainsString('<Movary & Co>', $message);
    }
}
