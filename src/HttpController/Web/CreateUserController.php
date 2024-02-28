<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class CreateUserController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UserApi $userApi,
    ) {
    }

    public function renderPage() : Response
    {
        $hasUsers = $this->userApi->hasUsers();

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/create-user.html.twig', [
                'subtitle' => $hasUsers === false ? 'Create initial admin user' : 'Create new user',
            ]),
        );
    }
}
