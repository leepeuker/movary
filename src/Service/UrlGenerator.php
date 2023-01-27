<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\Api\Tmdb\TmdbUrlGenerator;

class UrlGenerator
{
    public function __construct(
        private readonly TmdbUrlGenerator $tmdbUrlGenerator,
        private readonly ImageCacheService $imageCacheService,
        private readonly bool $enableImageCaching,
    ) {
    }

    public function generateImageBackdropSrcUrlFromParameters(?string $tmdbBackdropPath) : string
    {
        if (empty($tmdbBackdropPath) === false) {
            return (string)$this->tmdbUrlGenerator->generateImageUrl($tmdbBackdropPath, 'w1280');
        }

        return '/images/placeholder-image.png';
    }

    public function generateImagePosterSrcUrlFromParameters(?string $tmdbPosterPath, ?string $posterPath = null) : string
    {
        if ($this->enableImageCaching === true && empty($posterPath) === false && $this->imageCacheService->posterPathExists($posterPath) === true) {
            return '/' . trim($posterPath, '/');
        }

        if (empty($tmdbPosterPath) === false) {
            return (string)$this->tmdbUrlGenerator->generateImageUrl($tmdbPosterPath);
        }

        return '/images/placeholder-image.png';
    }

    public function replacePosterPathWithImageSrcUrl(array $dbResults) : array
    {
        foreach ($dbResults as $index => $dbResult) {
            $dbResults[$index]['poster_path'] = $this->generateImagePosterSrcUrlFromParameters(
                $dbResult['tmdb_poster_path'] ?? null,
                $dbResult['poster_path'] ?? null,
            );
        }

        return $dbResults;
    }
}
