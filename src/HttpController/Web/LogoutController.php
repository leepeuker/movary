<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\ValueObject\Http\Response;

class LogoutController
{
    public function __construct(private readonly Authentication $authenticationService)
    {
    }

    public function logout() : Response
    {
        $this->authenticationService->logout();

        return Response::createNoContent();
    }
}
