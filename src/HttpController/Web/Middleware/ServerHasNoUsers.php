<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Response;

class ServerHasNoUsers implements MiddlewareInterface
{
    public function __construct(
        private readonly UserApi $userApi,
    ) {
    }

    public function __invoke() : ?Response
    {
        if ($this->userApi->hasUsers() === true) {
            return null;
        }

        return Response::createSeeOther('/create-user');
    }
}
