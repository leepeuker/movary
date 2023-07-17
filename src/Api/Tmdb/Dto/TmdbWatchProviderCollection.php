<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class TmdbWatchProviderCollection
{
    private function __construct(
        private readonly TmdbWatchProviderList $flatrateProviders,
        private readonly TmdbWatchProviderList $rentProviders,
        private readonly TmdbWatchProviderList $buyProviders,
        private readonly TmdbWatchProviderList $adsProviders,
        private readonly TmdbWatchProviderList $freeProviders,
    ) {
    }

    public static function create(
        TmdbWatchProviderList $flatrateProviders,
        TmdbWatchProviderList $rentProviders,
        TmdbWatchProviderList $buyProviders,
        TmdbWatchProviderList $adsProviders,
        TmdbWatchProviderList $freeProviders,
    ) : self {
        return new self($flatrateProviders, $rentProviders, $buyProviders, $adsProviders, $freeProviders);
    }

    public function getAdsProviders() : TmdbWatchProviderList
    {
        return $this->adsProviders;
    }

    public function getAll() : TmdbWatchProviderList
    {
        $uniqueProviders = TmdbWatchProviderList::create();

        $uniqueProviders->addUniqueProviders($this->buyProviders);
        $uniqueProviders->addUniqueProviders($this->rentProviders);
        $uniqueProviders->addUniqueProviders($this->freeProviders);
        $uniqueProviders->addUniqueProviders($this->adsProviders);
        $uniqueProviders->addUniqueProviders($this->flatrateProviders);

        return $uniqueProviders->sortByDisplayPriority();
    }

    public function getBuyProviders() : TmdbWatchProviderList
    {
        return $this->buyProviders;
    }

    public function getFlatrateProviders() : TmdbWatchProviderList
    {
        return $this->flatrateProviders;
    }

    public function getFreeProviders() : TmdbWatchProviderList
    {
        return $this->freeProviders;
    }

    public function getRentProviders() : TmdbWatchProviderList
    {
        return $this->rentProviders;
    }
}
