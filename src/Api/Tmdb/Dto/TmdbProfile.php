<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class TmdbProfile
{
    public function __construct(
        private readonly string $path,
        private readonly string $previewUrl,
    ) {
    }

    public function getPath() : string
    {
        return $this->path;
    }

    /** @return array{path: string, previewUrl: string} */
    public function asArray() : array
    {
        return ['path' => $this->path, 'previewUrl' => $this->previewUrl];
    }
}
