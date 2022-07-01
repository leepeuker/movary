<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\SessionService;
use Movary\Application\User\Exception\InvalidPassword;
use Movary\Application\User\Service;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class AuthenticationController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Service\Authentication $authenticationService,
    ) {
    }

    public function login(Request $request) : Response
    {
        try {
            $this->authenticationService->login(
                $request->getPostParameters()['password'],
                isset($request->getPostParameters()['rememberMe']) === true
            );
        } catch (InvalidPassword) {
            $_SESSION['failedLogin'] = true;
        }

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }

    public function logout() : Response
    {
        $this->authenticationService->logout();

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation('/')]
        );
    }

    public function renderLoginPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === true) {
            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation('/')]
            );
        }

        $renderedTemplate = $this->twig->render('page/login.html.twig', ['failedLogin' => empty($_SESSION['failedLogin']) === false]);

        unset($_SESSION['failedLogin']);

        return Response::create(
            StatusCode::createOk(),
            $renderedTemplate,
        );
    }
}
