<?php declare(strict_types=1);

namespace Movary\Service\Email;

class PasswordResetEmailRenderer extends AbstractEmailRenderer
{
    public function render(string $resetUrl, int $expirationTimeInMinutes) : string
    {
        return $this->renderTemplate('password-reset', [
            'resetUrl' => $resetUrl,
            'expirationTimeInMinutes' => $expirationTimeInMinutes,
        ]);
    }
}
