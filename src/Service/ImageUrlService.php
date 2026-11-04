<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\ValueObject\RelativeUrl;

class ImageUrlService
{
    private const string PLACEHOLDER_IMAGE_NAME_FALLBACK = 'NO IMAGE';

    public function __construct(
        private readonly TmdbUrlGenerator $tmdbUrlGenerator,
        private readonly ImageCacheService $imageCacheService,
        private readonly ApplicationUrlService $urlService,
        private readonly bool $enableImageCaching,
    ) {
    }

    public function generateImageSrcUrlFromParameters(
        ?string $tmdbPosterPath,
        ?string $posterPath,
        ?string $fallbackName = self::PLACEHOLDER_IMAGE_NAME_FALLBACK,
    ) : string {
        if ($this->enableImageCaching === true && empty($posterPath) === false && $this->imageCacheService->posterPathExists($posterPath) === true) {
            return $this->urlService->createApplicationUrl(RelativeUrl::create('/' . trim($posterPath, '/')));
        }

        if (empty($tmdbPosterPath) === false) {
            return (string)$this->tmdbUrlGenerator->generateImageUrl($tmdbPosterPath);
        }

        if ($fallbackName == null) {
            $fallbackName = self::PLACEHOLDER_IMAGE_NAME_FALLBACK;
        }

        return $this->urlService->createApplicationUrl(RelativeUrl::create('/images/placeholder/' . base64_encode($fallbackName)));
    }

    public function generateMovieBackdropUrl(int $movieId, ?string $tmdbBackdropPath) : ?string
    {
        if (empty($tmdbBackdropPath) === true) {
            return null;
        }

        $imageUrl = $this->tmdbUrlGenerator->generateImageUrl($tmdbBackdropPath, 'w1280');
        if ($this->enableImageCaching === true) {
            $cachedPath = $this->imageCacheService->generateMovieBackdropPath($movieId, $imageUrl);
            if ($this->imageCacheService->posterPathExists($cachedPath) === true) {
                return $this->urlService->createApplicationUrl(RelativeUrl::create($cachedPath));
            }
        }

        return (string)$imageUrl;
    }

    public function replacePosterPathWithImageSrcUrl(array $dbResults) : array
    {
        foreach ($dbResults as $index => $dbResult) {
            $dbResults[$index]['poster_path'] = $this->generateImageSrcUrlFromParameters(
                $dbResult['tmdb_poster_path_override'] ?? $dbResult['tmdb_poster_path'] ?? null,
                $dbResult['poster_path'] ?? null,
                $dbResult['name'] ?? $dbResult['title'] ?? self::PLACEHOLDER_IMAGE_NAME_FALLBACK,
            );
        }

        return $dbResults;
    }
}
