<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\Url;
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
        $httpReferer = $request->getHttpReferer();
        if ($httpReferer !== null && $httpReferer != '') {
            $url = Url::createFromString($httpReferer);
            $httpReferer = $url->getPath();
        } else  {
            $httpReferer = null;
        }

        return Response::create(
            StatusCode::createNotFound(),
            $this->twig->render(
                'page/404.html.twig',
                [
                    'referer' => $httpReferer,
                    'currentUrl' => $request->getPath(),
                ],
            ),
        );
    }
}
