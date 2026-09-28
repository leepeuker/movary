<?php declare(strict_types=1);

namespace Movary\Service\Email;

use Movary\Service\ServerSettings;
use Twig\Environment;

abstract class AbstractEmailRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ServerSettings $serverSettings,
    ) {
    }

    /** @param array<string, mixed> $context */
    protected function renderTemplate(string $template, array $context = []) : string
    {
        return $this->twig->render('email/' . $template . '.html.twig', [
            'applicationName' => $this->serverSettings->getApplicationName() ?? 'Movary',
            ...$context,
        ]);
    }
}
