<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\ValueObject\Http\Response;

class ServerHasRegistrationEnabled
{
    public function __construct(
        private bool $registrationEnabled,
    ) {
    }

    public function __invoke() : ?Response
    {
        if ($this->registrationEnabled === true) {
            return Response::createSeeOther("/");
        }

        return null;
    }
}
