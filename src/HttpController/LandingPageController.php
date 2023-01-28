<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\Config;
use Twig\Environment;

class LandingPageController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly Config $config,
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
            return Response::createSeeOther('/create-user');
        }

        $failedLogin = $this->sessionWrapper->has('failedLogin');
        $deletedAccount = $this->sessionWrapper->has('deletedAccount');
        $registration = $this->config->getAsString('REGISTRATION', 'false');

        $this->sessionWrapper->unset('failedLogin', 'deletedAccount');

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/login.html.twig', [
                'failedLogin' => $failedLogin,
                'deletedAccount' => $deletedAccount,
                'registration' => $registration
            ]),
        );
    }
}
