<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Psr\Log\LoggerInterface;

class HttpRefererIsAllowed implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        $httpReferer = $request->getHttpReferer();
        if ($httpReferer === null) {
            $this->logger->warning('Missing HTTP Referer header');

            return Response::createBadRequest();
        }

        $httpHost = $request->getHttpHost();
        if ($httpHost === null) {
            $this->logger->warning('Missing HTTP Host header');

            return Response::createBadRequest();
        }

        $refererHost = parse_url($httpReferer, PHP_URL_HOST);
        $requestHost = parse_url($httpHost, PHP_URL_HOST);

        if ($refererHost !== $requestHost) {
            $this->logger->warning('HTTP Referer not allowed', ['referer' => $httpReferer, 'host' => $requestHost]);

            return Response::createForbidden();
        }

        return null;
    }
}
