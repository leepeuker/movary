<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Response;

class StartSession implements MiddlewareInterface
{
    public function __construct(
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    public function __invoke() : ?Response
    {
        $this->sessionWrapper->start();

        return null;
    }
}
