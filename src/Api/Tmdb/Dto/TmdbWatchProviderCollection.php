<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class TmdbWatchProviderCollection
{
    private function __construct(
        private readonly TmdbWatchProviderList $flatrate,
        private readonly TmdbWatchProviderList $rent,
        private readonly TmdbWatchProviderList $buy,
        private readonly TmdbWatchProviderList $ads,
        private readonly TmdbWatchProviderList $free,
    ) {
    }

    public static function create(
        TmdbWatchProviderList $flatrate,
        TmdbWatchProviderList $rent,
        TmdbWatchProviderList $buy,
        TmdbWatchProviderList $ads,
        TmdbWatchProviderList $free,
    ) : self {
        return new self($flatrate, $rent, $buy, $ads, $free);
    }

    public function getAds() : TmdbWatchProviderList
    {
        return $this->ads;
    }

    public function getBuy() : TmdbWatchProviderList
    {
        return $this->buy;
    }

    public function getFlatrate() : TmdbWatchProviderList
    {
        return $this->flatrate;
    }

    public function getFree() : TmdbWatchProviderList
    {
        return $this->free;
    }

    public function getRent() : TmdbWatchProviderList
    {
        return $this->rent;
    }
}
