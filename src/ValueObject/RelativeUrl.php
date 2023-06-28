<?php declare(strict_types=1);

namespace Movary\ValueObject;

use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;

class RelativeUrl implements JsonSerializable
{
    //region instancing
    private function __construct(private readonly string $relativeUrl)
    {
        $this->ensureIsValidRelativeUrl($relativeUrl);
    }
    //endregion instancing

    //region methods
    public static function createFromString(string $url) : self
    {
        return new self($url);
    }

    public function __toString() : string
    {
        return $this->relativeUrl;
    }

    /**
     * @throws RuntimeException
     * @psalm-suppress TypeDoesNotContainType
     */
    public function getQuery() : ?string
    {
        $query = parse_url($this->relativeUrl, PHP_URL_QUERY);

        if ($query === false) {
            throw new RuntimeException(sprintf('Could not parse query from url "%s"', $this->relativeUrl)); // @codeCoverageIgnore
        }

        return $query;
    }

    public function isEqual(RelativeUrl $relativeUrl) : bool
    {
        return $this->relativeUrl === $relativeUrl->relativeUrl;
    }

    public function jsonSerialize() : string
    {
        return $this->relativeUrl;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function ensureIsValidRelativeUrl(string $url) : void
    {
        if (str_starts_with($url, '/') === false) {
            throw new InvalidArgumentException('Relative URL must start with a leading slash: ' . $url);
        }

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            throw new InvalidArgumentException('Invalid relative url: ' . $url);  // @codeCoverageIgnore
        }
    }
    //endregion methods
}
