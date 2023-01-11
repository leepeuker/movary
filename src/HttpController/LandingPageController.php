<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class LandingPageController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    public function render() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === true) {
            $userName = $this->authenticationService->getCurrentUser()->getName();

            return Response::createSeeOther("/users/$userName/dashboard");
        }

        if ($this->userApi->hasUsers() === false) {
            $errorPasswordTooShort = $this->sessionWrapper->find('errorPasswordTooShort');
            $errorPasswordNotEqual = $this->sessionWrapper->find('errorPasswordNotEqual');
            $errorUsernameInvalidFormat = $this->sessionWrapper->find('errorUsernameInvalidFormat');
            $errorGeneric = $this->sessionWrapper->find('errorGeneric');

            $this->sessionWrapper->unset('errorPasswordTooShort', 'errorPasswordNotEqual', 'errorUsernameInvalidFormat', 'errorGeneric');

            return Response::create(
                StatusCode::createOk(),
                $this->twig->render('page/create-user.html.twig', [
                    'errorPasswordTooShort' => $errorPasswordTooShort,
                    'errorPasswordNotEqual' => $errorPasswordNotEqual,
                    'errorUsernameInvalidFormat' => $errorUsernameInvalidFormat,
                    'errorGeneric' => $errorGeneric,
                ]),
            );
        }

        $failedLogin = $this->sessionWrapper->has('failedLogin');
        $deletedAccount = $this->sessionWrapper->has('deletedAccount');

        $this->sessionWrapper->unset('failedLogin', 'deletedAccount');

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/login.html.twig', [
                'failedLogin' => $failedLogin,
                'deletedAccount' => $deletedAccount,
            ]),
        );
    }
}
