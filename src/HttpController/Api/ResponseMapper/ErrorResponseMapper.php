<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\Util\Json;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class ErrorResponseMapper
{
    public function mapInvalidRequest(InvalidApiRequest $exception) : Response
    {
        return $this->createResponse(
            $exception->getStatusCode(),
            $exception->getErrorType(),
            $exception->getMessage(),
        );
    }

    public function mapInternalServerError() : Response
    {
        return $this->createResponse(
            StatusCode::createInternalServerError(),
            'InternalServerError',
            'The request could not be processed.',
        );
    }

    private function createResponse(StatusCode $statusCode, string $errorType, string $message) : Response
    {
        return Response::createJson(
            Json::encode([
                'error' => $errorType,
                'message' => $message,
            ]),
            $statusCode,
        );
    }
}
