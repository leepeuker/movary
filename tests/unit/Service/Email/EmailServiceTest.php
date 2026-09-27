<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Movary\Service\Email\CannotSendEmailException;
use Movary\Service\Email\EmailService;
use Movary\Service\Email\SmtpConfig;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\Email\EmailService::class)]
#[CoversClass(\Movary\Service\Email\CannotSendEmailException::class)]
#[AllowMockObjectsWithoutExpectations]
class EmailServiceTest extends TestCase
{
    /** @var PHPMailer&MockObject */
    private PHPMailer $phpMailerMock;

    private EmailService $subject;

    public function setUp() : void
    {
        $this->phpMailerMock = $this->getMockBuilder(PHPMailer::class)
            ->onlyMethods(['send', 'isError'])
            ->getMock();
        $this->subject = new EmailService($this->phpMailerMock);
    }

    public function testSendEmailConfiguresMailerAndClearsPreviousMessageState() : void
    {
        $this->phpMailerMock->addAddress('stale@example.com');
        $this->phpMailerMock->addReplyTo('reply@example.com');
        $this->phpMailerMock->addStringAttachment('content', 'stale.txt');
        $this->phpMailerMock->addCustomHeader('X-Stale', 'value');
        $this->phpMailerMock->expects(self::once())->method('send')->willReturn(true);
        $this->phpMailerMock->expects(self::once())->method('isError')->willReturn(false);

        $this->subject->sendEmail(
            'target@example.com',
            'Subject',
            '<p>Hello <strong>world</strong></p>',
            $this->createSmtpConfig(),
        );

        self::assertSame([['target@example.com', '']], $this->phpMailerMock->getToAddresses());
        self::assertSame([], $this->phpMailerMock->getReplyToAddresses());
        self::assertSame([], $this->phpMailerMock->getAttachments());
        self::assertSame([], $this->phpMailerMock->getCustomHeaders());
        self::assertSame('smtp', $this->phpMailerMock->Mailer);
        self::assertSame('smtp.example.com', $this->phpMailerMock->Host);
        self::assertSame(587, $this->phpMailerMock->Port);
        self::assertSame('sender@example.com', $this->phpMailerMock->From);
        self::assertSame('tls', $this->phpMailerMock->SMTPSecure);
        self::assertTrue($this->phpMailerMock->SMTPAuth);
        self::assertSame('user', $this->phpMailerMock->Username);
        self::assertSame('secret', $this->phpMailerMock->Password);
        self::assertSame(PHPMailer::CHARSET_UTF8, $this->phpMailerMock->CharSet);
        self::assertSame(PHPMailer::CONTENT_TYPE_TEXT_HTML, $this->phpMailerMock->ContentType);
        self::assertSame(30, $this->phpMailerMock->Timeout);
        self::assertSame(30, $this->phpMailerMock->getSMTPInstance()->Timelimit);
        self::assertSame('Subject', $this->phpMailerMock->Subject);
        self::assertSame('<p>Hello <strong>world</strong></p>', $this->phpMailerMock->Body);
        self::assertStringContainsString('Hello world', $this->phpMailerMock->AltBody);
    }

    public function testSendEmailDoesNotRetainRecipientBetweenSends() : void
    {
        $this->phpMailerMock->expects(self::exactly(2))->method('send')->willReturn(true);
        $this->phpMailerMock->expects(self::exactly(2))->method('isError')->willReturn(false);

        $this->subject->sendEmail('first@example.com', 'First', 'First body', $this->createSmtpConfig());
        $this->subject->sendEmail('second@example.com', 'Second', 'Second body', $this->createSmtpConfig());

        self::assertSame([['second@example.com', '']], $this->phpMailerMock->getToAddresses());
    }

    public function testSendEmailReportsTransportFailure() : void
    {
        $this->phpMailerMock->ErrorInfo = 'SMTP transport failed';
        $this->phpMailerMock->expects(self::once())->method('send')->willReturn(false);
        $this->phpMailerMock->expects(self::never())->method('isError');

        $this->expectException(CannotSendEmailException::class);
        $this->expectExceptionMessage('SMTP transport failed');

        $this->subject->sendEmail('target@example.com', 'Subject', 'Body', $this->createSmtpConfig());
    }

    public function testSendEmailWrapsPhpMailerException() : void
    {
        $phpMailerException = new PHPMailerException('PHPMailer failed');
        $this->phpMailerMock->expects(self::once())->method('send')->willThrowException($phpMailerException);

        try {
            $this->subject->sendEmail('target@example.com', 'Subject', 'Body', $this->createSmtpConfig());
            self::fail('Expected CannotSendEmailException was not thrown.');
        } catch (CannotSendEmailException $e) {
            self::assertSame('PHPMailer failed', $e->getMessage());
            self::assertSame($phpMailerException, $e->getPrevious());
        }
    }

    public function testSendEmailRejectsInvalidTargetAddress() : void
    {
        $this->phpMailerMock->expects(self::never())->method('send');

        $this->expectException(CannotSendEmailException::class);
        $this->expectExceptionMessage('Target email address must be valid.');

        $this->subject->sendEmail('invalid', 'Subject', 'Body', $this->createSmtpConfig());
    }

    private function createSmtpConfig() : SmtpConfig
    {
        return SmtpConfig::create(
            'smtp.example.com',
            587,
            'sender@example.com',
            'tls',
            true,
            'user',
            'secret',
        );
    }
}
