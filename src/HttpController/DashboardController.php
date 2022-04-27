<?php declare(strict_types=1);

namespace Movary\HttpController;

use Twig\Environment;

class DashboardController
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function render() : void
    {
        echo $this->twig->render('dashboard.html.twig');
    }
}
