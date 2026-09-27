<?php declare(strict_types=1);

namespace Movary\Service\Email;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class EmailService
{
    private const int SMTP_TIMEOUT_IN_SECONDS = 30;

    public function __construct(
        private PHPMailer $phpMailer,
    ) {
    }

    public function sendEmail(string $targetEmailAddress, string $subject, string $htmlMessage, SmtpConfig $smtpConfig) : void
    {
        $this->resetMessage();

        try {
            $this->configureMailer($smtpConfig);

            if ($this->phpMailer->addAddress($targetEmailAddress) === false) {
                throw new CannotSendEmailException('Target email address must be valid.');
            }

            $this->phpMailer->Subject = $subject;
            $this->phpMailer->Body = $htmlMessage;
            $this->phpMailer->AltBody = $this->phpMailer->html2text($htmlMessage);

            if ($this->phpMailer->send() === false || $this->phpMailer->isError() === true) {
                $errorMessage = trim($this->phpMailer->ErrorInfo) === ''
                    ? 'Could not send email.'
                    : $this->phpMailer->ErrorInfo;

                throw new CannotSendEmailException($errorMessage);
            }
        } catch (PHPMailerException $e) {
            throw new CannotSendEmailException($e->getMessage(), 0, $e);
        }
    }

    private function configureMailer(SmtpConfig $smtpConfig) : void
    {
        $this->phpMailer->SMTPDebug = SMTP::DEBUG_OFF;
        $this->phpMailer->Timeout = self::SMTP_TIMEOUT_IN_SECONDS;
        $this->phpMailer->getSMTPInstance()->Timelimit = self::SMTP_TIMEOUT_IN_SECONDS;
        $this->phpMailer->CharSet = PHPMailer::CHARSET_UTF8;

        $this->phpMailer->isSMTP();
        $this->phpMailer->isHTML();
        $this->phpMailer->Host = $smtpConfig->getHost();
        $this->phpMailer->Port = $smtpConfig->getPort();
        $this->phpMailer->setFrom($smtpConfig->getFromAddress());
        $this->phpMailer->SMTPSecure = (string)$smtpConfig->getEncryption();

        $this->phpMailer->SMTPAuth = $smtpConfig->isWithAuthentication();
        $this->phpMailer->Username = (string)$smtpConfig->getUser();
        $this->phpMailer->Password = (string)$smtpConfig->getPassword();
    }

    private function resetMessage() : void
    {
        $this->phpMailer->clearAllRecipients();
        $this->phpMailer->clearReplyTos();
        $this->phpMailer->clearAttachments();
        $this->phpMailer->clearCustomHeaders();
    }
}
