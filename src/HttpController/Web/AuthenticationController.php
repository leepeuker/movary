<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\SessionService;
use Movary\Service\Email\EmailSupport;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class AuthenticationController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EmailSupport $emailSupport,
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
                'redirect' => $redirect,
                'passwordResetSuccessful' => $request->getGetParameters()['password-reset'] ?? null,
                'passwordResetAvailable' => $this->emailSupport->isPasswordResetAvailable(),
            ],
        );

        return Response::create(
            StatusCode::createOk(),
            $renderedTemplate,
        );
    }
}
