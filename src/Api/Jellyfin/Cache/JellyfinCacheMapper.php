<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Cache;

use Generator;
use Movary\Api\Jellyfin\Dto\JellyfinMovieDto;
use Movary\ValueObject\Date;

class JellyfinCacheMapper
{
    public function map(string $jellyfinUserId, Generator $jellyfinItemPages) : Generator
    {
        foreach ($jellyfinItemPages as $jellyfinPage) {
            foreach ($jellyfinPage['Items'] as $jellyfinMovie) {
                $tmdbId = $this->extractTmdbId($jellyfinMovie);

                if ($tmdbId === null) {
                    continue;
                }

                $watched = $jellyfinMovie['UserData']['Played'];
                $lastPlayedDate = isset($jellyfinMovie['UserData']['LastPlayedDate']) === true ? Date::createFromString($jellyfinMovie['UserData']['LastPlayedDate']) : null;
                $jellyfinItemId = $jellyfinMovie['Id'];

                yield JellyfinMovieDto::create(
                    $jellyfinUserId,
                    $jellyfinItemId,
                    $tmdbId,
                    $watched,
                    $lastPlayedDate,
                );
            }
        }
    }

    private function extractTmdbId(array $jellyfinMovie) : ?int
    {
        foreach ($jellyfinMovie['ProviderIds'] ?? [] as $provider => $id) {
            if ($provider === 'Tmdb') {
                return (int)$id;
            }
        }

        return null;
    }
}
