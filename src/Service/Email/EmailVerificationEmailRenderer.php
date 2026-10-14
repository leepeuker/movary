<?php declare(strict_types=1);

namespace Movary\Service\Email;

class EmailVerificationEmailRenderer extends AbstractEmailRenderer
{
    public function render(string $verificationUrl, int $expirationTimeInHours) : string
    {
        return $this->renderTemplate('email-verification', [
            'verificationUrl' => $verificationUrl,
            'expirationTimeInHours' => $expirationTimeInHours,
        ]);
    }
}
