<?php declare(strict_types=1);

namespace Movary\Service\Email;

use Movary\Service\ServerSettings;
use Twig\Environment;

class PasswordResetEmailRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function render(string $resetUrl, int $expirationTimeInMinutes) : string
    {
        return $this->twig->render('email/password-reset.html.twig', [
            'applicationName' => $this->serverSettings->getApplicationName() ?? 'Movary',
            'resetUrl' => $resetUrl,
            'expirationTimeInMinutes' => $expirationTimeInMinutes,
        ]);
    }
}
