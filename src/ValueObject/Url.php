<?php declare(strict_types=1);

namespace Movary\ValueObject;

use Movary\ValueObject\Exception\InvalidUrl;
use RuntimeException;

class Url
{
    private function __construct(private readonly string $url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw InvalidUrl::create($this->url);
        }
    }

    public static function createFromString(string $url) : self
    {
        return new self($url);
    }

    public function __toString() : string
    {
        return $this->url;
    }

    public function appendRelativeUrl(RelativeUrl $relativeUrl) : self
    {
        if (parse_url($this->url, PHP_URL_QUERY) !== null) {
            throw new RuntimeException(
                sprintf('Cannot append relative url "%s" to url "%s", because the url has query parameters', $relativeUrl, $this->url),
            );
        }

        return new self(rtrim($this->url, '/') . $relativeUrl);
    }

    public function getPath() : ?string
    {
        $path = parse_url($this->url, PHP_URL_PATH);

        if ($path === false) {
            throw new RuntimeException(sprintf('Could not parse path from url "%s"', $this->url));
        }

        return $path;
    }
}
