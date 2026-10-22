<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class TmdbPoster
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

    public function getPreviewUrl() : string
    {
        return $this->previewUrl;
    }

    /** @return array{path: string, previewUrl: string} */
    public function asArray() : array
    {
        return ['path' => $this->path, 'previewUrl' => $this->previewUrl];
    }
}
