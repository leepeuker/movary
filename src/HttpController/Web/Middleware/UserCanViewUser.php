<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\ValueObject\Http\Response;

class UserCanViewUser implements MiddlewareInterface
{
    public function __construct(
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
    ) {
    }

    public function __invoke() : ?Response
    {
    }
}
