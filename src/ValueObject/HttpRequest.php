<?php declare(strict_types=1);

namespace Movary\ValueObject;

class HttpRequest
{
    private string $body;

    private array $getParameters;

    private array $postParameters;

    private array $routeParameters = [];

    private function __construct(
        array $getParameters,
        array $postParameters,
        string $body
    ) {
        $this->getParameters = $getParameters;
        $this->postParameters = $postParameters;
        $this->body = $body;
    }

    public static function createFromGlobals() : self
    {
        $getParameterList = self::extractGetParameter();
        $postParameterList = self::extractPostParameter();

        $body = (string)file_get_contents('php://input');

        return new self($getParameterList, $postParameterList, $body);
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
