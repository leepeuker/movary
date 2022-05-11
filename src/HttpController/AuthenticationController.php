<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\User\Exception\InvalidPassword;
use Movary\Application\User\Service;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class AuthenticationController
{
    private Environment $twig;

    private Service\Login $userLoginService;

    public function __construct(Environment $twig, Service\Login $userLoginService)
    {
        $this->twig = $twig;
        $this->userLoginService = $userLoginService;
    }

    public function login(Request $request) : Response
    {
        if (isset($_SESSION['user']) === true) {
            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])]
            );
        }

        try {
            $this->userLoginService->authenticate(
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
        unset($_SESSION['user']);
        session_regenerate_id();

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation('/')]
        );
    }

    public function renderLoginPage() : Response
    {
        if (isset($_SESSION['user']) === true) {
            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation('/')]
            );
        }

        $renderedTemplate = $this->twig->render('login.html.twig', ['failedLogin' => empty($_SESSION['failedLogin']) === false]);

        unset($_SESSION['failedLogin']);

        return Response::create(
            StatusCode::createOk(),
            $renderedTemplate,
        );
    }
}
