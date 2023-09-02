<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

readonly class OpenApiController
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    public function renderPage() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/api.html.twig'),
        );
    }
}