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

    public function sendEmail(string $targetEmailAddress, string $subject, string $htmlMessage) : void
    {
        $this->phpMailer->SMTPDebug = SMTP::DEBUG_SERVER;

        $this->phpMailer->isSMTP();
        $this->phpMailer->Host = $this->serverSettings->getSmtpHost();
        $this->phpMailer->SMTPAuth = $this->serverSettings->getSmtpWithAuthentication();
        $this->phpMailer->Username = $this->serverSettings->getSmtpUser();
        $this->phpMailer->Password = $this->serverSettings->getSmtpPassword();
        if ($this->serverSettings->getSmtpPort() === 587) {
            $this->phpMailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        $this->phpMailer->Port = $this->serverSettings->getSmtpPort();
        $this->phpMailer->setFrom($this->serverSettings->getFromAddress());

        $this->phpMailer->addAddress($targetEmailAddress);
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->Body = $htmlMessage;

        $this->phpMailer->send();
    }
}
