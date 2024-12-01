<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<TmdbWatchProviderDto>
 */
class TmdbWatchProviderList extends AbstractList
{
    public static function create(TmdbWatchProviderDto ...$tmdbWatchProviders) : self
    {
        $list = new self();

        foreach ($tmdbWatchProviders as $tmdbWatchProvider) {
            $list->add($tmdbWatchProvider);
        }

        return $list;
    }

    public static function createFromArray(array $data) : self
    {
        $list = self::create();

        foreach ($data as $genreDate) {
            $list->add(TmdbWatchProviderDto::createFromArray($genreDate));
        }

        return $list;
    }

    public function add(TmdbWatchProviderDto $watchProvider) : void
    {
        $this->data[$watchProvider->getId()] = $watchProvider;
    }

    public function addUniqueProviders(TmdbWatchProviderList $providers) : void
    {
        foreach ($providers as $provider) {
            $uniqueProvider = $this->getById($provider->getId());

            if ($uniqueProvider !== null && $uniqueProvider->getDisplayPriority() < $provider->getDisplayPriority()) {
                continue;
            }

            $this->add($provider);
        }
    }

    public function getById(int $id) : ?TmdbWatchProviderDto
    {
        if (isset($this->data[$id]) === true) {
            return $this->data[$id];
        }

        return null;
    }

    public function sortByDisplayPriority() : self
    {
        $data = $this->asArray();

        usort($data, function (TmdbWatchProviderDto $item1, TmdbWatchProviderDto $item2) {
            return $item1->getDisplayPriority() <=> $item2->getDisplayPriority();
        });

        return self::create(...$data);
    }
}
