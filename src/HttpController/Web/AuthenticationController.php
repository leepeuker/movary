<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\SessionService;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class AuthenticationController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    public function renderLoginPage(Request $request) : Response
    {
        $failedLogin = $this->sessionWrapper->has('failedLogin');
        $redirect = $request->getGetParameters()['redirect'] ?? false;
        $this->sessionWrapper->unset('failedLogin');

        $renderedTemplate = $this->twig->render(
            'page/login.html.twig',
            [
                'failedLogin' => $failedLogin,
                'redirect' => $redirect
            ],
        );

        return Response::create(
            StatusCode::createOk(),
            $renderedTemplate,
        );
    }
}
