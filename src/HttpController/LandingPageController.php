<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\User\Service\Authentication;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class LandingPageController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function render() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === true) {
            $userId = $this->authenticationService->getCurrentUserId();

            return Response::createFoundRedirect("/$userId/dashboard");
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
