<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\ValueObject\RelativeUrl;

class ImageUrlService
{
    public function __construct(
        private readonly TmdbUrlGenerator $tmdbUrlGenerator,
        private readonly ImageCacheService $imageCacheService,
        private readonly ApplicationUrlService $urlService,
        private readonly bool $enableImageCaching,
    ) {
    }

    public function generateImageSrcUrlFromParameters(?string $tmdbPosterPath, ?string $posterPath) : string
    {
        if ($this->enableImageCaching === true && empty($posterPath) === false && $this->imageCacheService->posterPathExists($posterPath) === true) {
            return $this->urlService->createApplicationUrl(RelativeUrl::create('/' . trim($posterPath, '/')));
        }

        if (empty($tmdbPosterPath) === false) {
            return (string)$this->tmdbUrlGenerator->generateImageUrl($tmdbPosterPath);
        }

        return $this->urlService->createApplicationUrl(RelativeUrl::create('/images/placeholder-image.png'));
    }

    public function replacePosterPathWithImageSrcUrl(array $dbResults) : array
    {
        foreach ($dbResults as $index => $dbResult) {
            $imageSrcUrl = $this->generateImageSrcUrlFromParameters(
                $dbResult['tmdb_poster_path'] ?? null,
                $dbResult['poster_path'] ?? null,
            );
            if (str_ends_with($imageSrcUrl, "/images/placeholder-image.png"))
                $dbResults[$index]['poster_path_is_placeholder'] = true;
            else
                $dbResults[$index]['poster_path_is_placeholder'] = false;
                        
            $dbResults[$index]['poster_path'] = $imageSrcUrl;
        }

        return $dbResults;
    }
}
