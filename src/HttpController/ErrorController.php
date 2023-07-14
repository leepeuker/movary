<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class ErrorController
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function renderInternalServerError() : Response
    {
        return Response::create(
            StatusCode::createInternalServerError(),
            $this->twig->render('page/500.html.twig'),
        );
    }

    public function renderNotFound(Request $request) : Response
    {
        return Response::create(
            StatusCode::createNotFound(),
            $this->twig->render(
                'page/404.html.twig',
                [
                    'referer' => $this->getReferer($request)
                ],
            ),
        );
    }

    private function getReferer(Request $request) : ?string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer === null) {
            return null;
        }

        if (parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) === $request->getPath()) {
            return null;
        }

        return $_SERVER['HTTP_REFERER'];
    }
}
