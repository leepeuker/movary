<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Exception;

use RuntimeException;

class InvalidApiRequest extends RuntimeException
{
    private function __construct(
        private readonly string $errorType,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function invalidJson() : self
    {
        return new self('InvalidJson', 'Request body must contain a valid JSON array.');
    }

    public static function invalidJsonObject() : self
    {
        return new self('InvalidJson', 'Request body must contain a valid JSON object.');
    }

    public static function invalidField(string $path, string $requirement) : self
    {
        return new self('InvalidRequest', sprintf('Field "%s" %s.', $path, $requirement));
    }

    public static function movieNotFound(int $movieId) : self
    {
        return new self('MovieNotFound', sprintf('Movie with id %d does not exist.', $movieId));
    }

    public static function missingProviderId() : self
    {
        return new self('MissingProviderId', 'No external provider id submitted');
    }

    public function getErrorType() : string
    {
        return $this->errorType;
    }
}
