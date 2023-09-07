<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Response;

class ServerHasUsers implements MiddlewareInterface
{
    public function __construct(
        private readonly UserApi $userApi,
    ) {
    }

    public function __invoke() : ?Response
    {
        if ($this->userApi->hasUsers() === false) {
            return null;
        }

        return Response::createSeeOther('/');
    }
}
