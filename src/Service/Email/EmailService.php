<?php declare(strict_types=1);

namespace Movary\Service\Email;

use Movary\Service\ServerSettings;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class EmailService
{
    public function __construct(
        private PHPMailer $phpMailer,
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function getSmtpConfig() : SmtpConfig
    {
        return SmtpConfig::create(
            $this->serverSettings->getSmtpHost(),
            $this->serverSettings->getSmtpPort(),
            $this->serverSettings->getFromAddress(),
            $this->serverSettings->getSmtpEncryption(),
            $this->serverSettings->getSmtpWithAuthentication(),
            $this->serverSettings->getSmtpUser(),
            $this->serverSettings->getSmtpPassword(),
        );
    }

    public function sendEmail(string $targetEmailAddress, string $subject, string $htmlMessage, SmtpConfig $smtpConfig) : void
    {
        $this->phpMailer->SMTPDebug = SMTP::DEBUG_OFF;

        if ($smtpConfig->getHost() === '') {
            throw new CannotSendEmailException('SMTP host must be set.');
        }

        if ($smtpConfig->getPort() === 0) {
            throw new CannotSendEmailException('SMTP port must be set.');
        }

        $this->phpMailer->isSMTP();
        $this->phpMailer->Host = $smtpConfig->getHost();
        $this->phpMailer->Port = $smtpConfig->getPort();
        $this->phpMailer->setFrom($smtpConfig->getFromAddress());
        $this->phpMailer->SMTPSecure = (string)$smtpConfig->getEncryption();

        $this->phpMailer->SMTPAuth = $smtpConfig->isWithAuthentication();
        $this->phpMailer->Username = (string)$smtpConfig->getUser();
        $this->phpMailer->Password = (string)$smtpConfig->getPassword();

        $this->phpMailer->addAddress($targetEmailAddress);
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->Body = $htmlMessage;

        if ($this->phpMailer->send() === false || $this->phpMailer->isError() === true) {
            throw new CannotSendEmailException($this->phpMailer->ErrorInfo);
        }
    }
}
