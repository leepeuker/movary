<?php declare(strict_types=1);

namespace Movary\Service\Email;

use Movary\Service\ServerSettings;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

abstract class AbstractEmailRenderer
{
    private readonly Environment $twig;

    public function __construct(
        LoaderInterface $loader,
        private readonly ServerSettings $serverSettings,
    ) {
        $this->twig = new Environment($loader);
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
