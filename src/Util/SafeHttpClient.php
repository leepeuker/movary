<?php declare(strict_types=1);

namespace Movary\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Movary\ValueObject\Url;
use Psr\Http\Message\ResponseInterface;

class SafeHttpClient
{
    private readonly Client $client;

    private readonly bool $usesCurlHandler;

    /**
     * @param array<int, string> $allowlist
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly UrlValidator $urlValidator,
        private readonly array $allowlist,
        array $config = [],
        ?bool $usesCurlHandler = null,
    ) {
        $this->usesCurlHandler = $usesCurlHandler ?? extension_loaded('curl');

        if (isset($config['handler']) === false) {
            $handler = $this->usesCurlHandler ? new CurlHandler() : new StreamHandler();
            $config['handler'] = HandlerStack::create($handler);
        }

        $config[RequestOptions::PROXY] = '';
        $config[RequestOptions::TIMEOUT] ??= 4;

        $this->client = new Client($config);
    }

    /** @param array<string, mixed> $options */
    public function request(string $method, string $url, array $options = []) : ResponseInterface
    {
        [$url, $options] = $this->secureRequest((string)Utils::uriFor($url), $options);

        return $this->client->request($method, $url, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array{string, array<string, mixed>}
     */
    private function secureRequest(string $url, array $options) : array
    {
        $url = Url::createFromString($url);
        $validatedUrl = $this->urlValidator->validateUrlIsSafe($url, $this->allowlist);

        $options[RequestOptions::ALLOW_REDIRECTS] = false;
        $options[RequestOptions::PROXY] = '';

        if ($this->usesCurlHandler === true) {
            $curlOptions = $options[RequestOptions::CURL] ?? [];
            $curlOptions[constant('CURLOPT_RESOLVE')] = [$validatedUrl->createCurlResolveEntry()];
            $options[RequestOptions::CURL] = $curlOptions;

            return [(string)$url, $options];
        }

        $headers = $options[RequestOptions::HEADERS] ?? [];
        $headers['Host'] = $validatedUrl->getHostHeader();
        $options[RequestOptions::HEADERS] = $headers;
        if ($validatedUrl->getScheme() === 'https') {
            $options[RequestOptions::STREAM_CONTEXT]['ssl']['peer_name'] = $validatedUrl->getHost();
        }

        return [$validatedUrl->createPinnedUrl($url), $options];
    }
}
