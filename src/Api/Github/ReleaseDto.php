<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Movary\ValueObject\Url;

class ReleaseDto
{
    private function __construct(
        private readonly string $name,
        private readonly Url $url,
    ) {
    }

    public static function create(string $name, Url $url) : self
    {
        return new self($name, $url);
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getUrl() : Url
    {
        return $this->url;
    }
}
