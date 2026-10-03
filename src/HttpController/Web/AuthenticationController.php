<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Service\Email\EmailSupport;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class AuthenticationController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EmailSupport $emailSupport,
        private readonly bool $registrationEnabled,
        private readonly ?string $defaultEmail,
        private readonly ?string $defaultPassword,
    ) {
    }

    public function renderLoginPage(Request $request) : Response
    {
        $redirect = $request->getGetParameters()['redirect'] ?? false;

        $passwordChanged = false;
        if (($request->getGetParameters()['password-change'] ?? null) === 'success') {
            $passwordChanged = true;
        }
        if (($request->getGetParameters()['password-reset'] ?? null) === 'success') {
            $passwordChanged = true;
        }

        $renderedTemplate = $this->twig->render(
            'page/login.html.twig',
            [
                'redirect' => $redirect,
                'passwordChanged' => $passwordChanged,
                'passwordResetAvailable' => $this->emailSupport->isPasswordResetAvailable(),
                'registrationEnabled' => $this->registrationEnabled,
                'defaultEmail' => $this->defaultEmail,
                'defaultPassword' => $this->defaultPassword,
            ],
        );

        return Response::create(
            StatusCode::createOk(),
            $renderedTemplate,
        );
    }
}
