<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\User\Api;
use Movary\Application\User\Service\Authentication;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class LandingPageController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Authentication $authenticationService,
        private readonly Api $userApi
    ) {
    }

    public function render() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === true) {
            $userName = $this->authenticationService->getCurrentUser()->getName();

            return Response::createFoundRedirect("/$userName/dashboard");
        }

        if ($this->userApi->hasUsers() === false) {
            $errorPasswordTooShort = $_SESSION['errorPasswordTooShort'];
            $errorPasswordNotEqual = $_SESSION['errorPasswordNotEqual'];
            $errorUsernameInvalidFormat = $_SESSION['errorUsernameInvalidFormat'];
            $errorGeneric = $_SESSION['errorGeneric'];

            unset(
                $_SESSION['errorPasswordTooShort'],
                $_SESSION['errorPasswordNotEqual'],
                $_SESSION['errorUsernameInvalidFormat'],
                $_SESSION['errorGeneric'],
            );

            return Response::create(
                StatusCode::createOk(),
                $this->twig->render('page/create-user.html.twig', [
                    'errorPasswordTooShort' => $errorPasswordTooShort,
                    'errorPasswordNotEqual' => $errorPasswordNotEqual,
                    'errorUsernameInvalidFormat' => $errorUsernameInvalidFormat,
                    'errorGeneric' => $errorGeneric,
                ])
            );
        }

        $failedLogin = $_SESSION['failedLogin'] ?? null;
        $deletedAccount = $_SESSION['deletedAccount'] ?? null;
        unset($_SESSION['failedLogin'], $_SESSION['deletedAccount']);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/login.html.twig', [
                'failedLogin' => empty($failedLogin) === false,
                'deletedAccount' => empty($deletedAccount) === false,
            ])
        );
    }
}
