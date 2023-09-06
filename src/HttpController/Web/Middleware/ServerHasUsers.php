<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Response;

class ServerHasUsers
{
    public function __construct(
        private readonly UserApi $userApi,
    ) {
    }

    public function main() : ?Response
    {
        if ($this->userApi->hasUsers() === true) {
            return Response::createSeeOther('/');
        }

        return null;
    }
}
