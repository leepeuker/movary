<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class DashboardController
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function render() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('dashboard.html.twig'),
        );
    }
}
