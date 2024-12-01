<?php declare(strict_types=1);

namespace Movary\ValueObject\Http;

use UnexpectedValueException;

class Request
{
    private array $routeParameters = [];

    private function __construct(
        private readonly string $path,
        private readonly array $getParameters,
        private readonly array $postParameters,
        private readonly string $body,
        private readonly array $filesParameters,
        private readonly array $headers,
        private readonly string $userAgent,
    ) {
    }

    public static function createFromGlobals() : self
    {
        $uri = self::extractRequestUri();
        $path = self::extractPath($uri);

        $getParameters = self::extractGetParameter();
        $postParameters = self::extractPostParameter();
        $filesParameters = self::extractFilesParameter();
        $headers = self::extractHeaders();
        $userAgent = self::extractUserAgent();

        $body = (string)file_get_contents('php://input');

        return new self($path, $getParameters, $postParameters, $body, $filesParameters, $headers, $userAgent);
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
            throw new UnexpectedValueException('Could not load GET parameters.');
        }

        return $getParameters;
    }

    private static function extractHeaders() : array
    {
        return getallheaders();
    }

    private static function extractPath(string $uri) : string
    {
        if (strpos($uri, 'https://') === 0 || strpos($uri, 'http://') === 0) {
            $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        } else {
            $parsableUrl = sprintf('http://%s%s', 'fakehost', $uri);
            $path = parse_url($parsableUrl, PHP_URL_PATH) ?? '/';
        }

        if (false === $path) {
            return '/';
        }

        return $path;
    }

    private static function extractPostParameter() : array
    {
        $postParameters = filter_input_array(INPUT_POST) ?? [];

        if ($postParameters === false) {
            throw new UnexpectedValueException('Could not load POST parameters.');
        }

        return $postParameters;
    }

    private static function extractRequestUri() : string
    {
        return self::getServerSetting('REQUEST_URI') ?? '';
    }

    private static function extractUserAgent() : string
    {
        return self::getServerSetting('HTTP_USER_AGENT') ?? '';
    }

    private static function getServerSetting(string $key) : ?string
    {
        return empty($_SERVER[$key]) === false ? (string)$_SERVER[$key] : null;
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

    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getPostParameters() : array
    {
        return $this->postParameters;
    }

    public function getRouteParameters() : array
    {
        return $this->routeParameters;
    }

    public function getUserAgent() : string
    {
        return $this->userAgent;
    }
}
