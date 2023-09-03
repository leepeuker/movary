<?php declare(strict_types=1);

namespace Movary\HttpController\Middleware;

use Movary\ValueObject\Http\Response;

class RegistrationEnabledCheck
{
    public function __construct(
        private bool $registrationEnabled
    ) {}

    public function main() : ?Response
    {
        if ($this->registrationEnabled === false) {
            return Response::createSeeOther("/");
        }
        return null;
    }
}