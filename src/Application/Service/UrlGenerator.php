<?php declare(strict_types=1);

namespace Movary\Application\Service;

use Movary\Api\Tmdb\TmdbUrlGenerator;

class UrlGenerator
{
    public function __construct(
        private readonly TmdbUrlGenerator $tmdbUrlGenerator,
        private readonly bool $enableImageCaching
    ) {
    }

    public function generateImageSrcUrlFromParameters(?string $tmdbPosterPath, ?string $posterPath) : string
    {
        if ($this->enableImageCaching === true && empty($posterPath) === false) {
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
            $dbResults[$index]['poster_path'] = $this->generateImageSrcUrlFromParameters($dbResult['tmdb_poster_path'], $dbResult['poster_path']);
        }

        return $dbResults;
    }
}
