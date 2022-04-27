<?php declare(strict_types=1);

namespace Movary\ValueObject;

class HttpRequest
{
    private array $routeParameters = [];

    private function __construct(
        private readonly array $getParameters,
        private readonly array $postParameters,
        private readonly string $body,
        private readonly array $filesParameters,
    ) {
    }

    public static function createFromGlobals() : self
    {
        $getParameterList = self::extractGetParameter();
        $postParameterList = self::extractPostParameter();
        $filesParameterList = self::extractFilesParameter();

        $body = (string)file_get_contents('php://input');

        return new self($getParameterList, $postParameterList, $body, $filesParameterList);
    }

    private static function extractFilesParameter() : array
    {
        // phpcs:ignore MySource.PHP.GetRequestData
        return $_FILES;
    }

    private static function extractGetParameter() : array
    {
        $getParameters = filter_input_array(INPUT_GET) ?? [];

        if ($getParameters === false) {
            throw new \UnexpectedValueException('Could not load GET parameters.');
        }

        return $getParameters;
    }

    private static function extractPostParameter() : array
    {
        $postParameters = filter_input_array(INPUT_POST) ?? [];

        if ($postParameters === false) {
            throw new \UnexpectedValueException('Could not load POST parameters.');
        }

        return $postParameters;
    }

    public function addRouteParameters(array $routeParameters) : void
    {
        $this->routeParameters = array_merge($this->routeParameters, $routeParameters);
    }

    public function getBody() : string
    {
        return $this->body;
    }

    public function getFileParameters() : array
    {
        return $this->filesParameters;
    }

    public function getGetParameters() : array
    {
        return $this->getParameters;
    }

    public function getPostParameters() : array
    {
        return $this->postParameters;
    }

    public function getRouteParameters() : array
    {
        return $this->routeParameters;
    }
}
