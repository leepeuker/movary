<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

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
        private readonly bool $registrationEnabled,
        private readonly ?string $defaultEmail,
        private readonly ?string $defaultPassword,
    ) {
    }

    public function render() : Response
    {
        // if ($this->authenticationService->isUserAuthenticated() === true) {
        //     $userName = $this->authenticationService->getCurrentUser()->getName();

        //     return Response::createSeeOther("/users/$userName/dashboard");
        // }

        // if ($this->userApi->hasUsers() === false) {
        //     return Response::createSeeOther('/create-user');
        // }

        $failedLogin = $this->sessionWrapper->has('failedLogin');
        $deletedAccount = $this->sessionWrapper->has('deletedAccount');
        $invalidTotpCode = $this->sessionWrapper->has('invalidTotpCode');
        $useTwoFactorAuthentication = $this->sessionWrapper->has('useTwoFactorAuthentication');

        $this->sessionWrapper->unset('failedLogin', 'deletedAccount', 'invalidTotpCode', 'useTwoFactorAuthentication');
        if ($invalidTotpCode === true) {
            $useTwoFactorAuthentication = true;
        }

        if ($useTwoFactorAuthentication === false) {
            $this->sessionWrapper->unset('rememberMe');
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/login.html.twig', [
                'failedLogin' => $failedLogin,
                'deletedAccount' => $deletedAccount,
                'registrationEnabled' => $this->registrationEnabled,
                'defaultEmail' => $this->defaultEmail,
                'defaultPassword' => $this->defaultPassword,
                'useTwoFactorAuthentication' => $useTwoFactorAuthentication,
                'invalidTotpCode' => $invalidTotpCode,
            ]),
        );
    }
}
