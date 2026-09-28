<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Service\Email\EmailSupport;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class PasswordResetIsAvailable implements MiddlewareInterface
{
    public function __construct(private readonly EmailSupport $emailSupport)
    {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        if ($this->emailSupport->isPasswordResetAvailable() === true) {
            return null;
        }

        return Response::createNotFound();
    }
}
