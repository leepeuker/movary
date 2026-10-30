<?php declare(strict_types=1);

namespace Movary\Service\Dashboard\Dto;

class DashboardRow
{
    public function __construct(
        private readonly int $id,
        private readonly string $key,
        private readonly string $name,
        private readonly bool $isVisible,
        private readonly bool $isExtended,
    ) {
    }

    public static function createLastPlays(bool $isVisible = true, bool $isExtended = true) : self
    {
        return self::create(0, 'last-plays', 'Last Plays', $isVisible, $isExtended);
    }

    public static function createLastPlaysCinema(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(11, 'last-plays-cinema', 'Last Plays Cinema', $isVisible, $isExtended);
    }

    public static function createMostWatchedActors(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(1, 'most-watched-actors', 'Most watched Actors', $isVisible, $isExtended);
    }

    public static function createMostWatchedActresses(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(2, 'most-watched-actresses', 'Most watched Actresses', $isVisible, $isExtended);
    }

    public static function createMostWatchedDirectors(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(3, 'most-watched-directors', 'Most watched Directors', $isVisible, $isExtended);
    }

    public static function createMostWatchedGenres(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(4, 'most-watched-genres', 'Most watched Genres', $isVisible, $isExtended);
    }

    public static function createMostWatchedLanguages(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(8, 'most-watched-languages', 'Most watched Languages', $isVisible, $isExtended);
    }

    public static function createMostWatchedProductionCompanies(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(6, 'most-watched-production-companies', 'Most watched Production Companies', $isVisible, $isExtended);
    }

    public static function createMostWatchedProductionCountries(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(12, 'most-watched-production-countries', 'Most watched Production Countries', $isVisible, $isExtended);
    }

    public static function createMostWatchedReleaseYears(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(7, 'most-watched-release-years', 'Most watched Release Years', $isVisible, $isExtended);
    }

    public static function createTopLocations(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(10, 'top-locations', 'Top Locations', $isVisible, $isExtended);
    }

    public static function createWatchlist(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(9, 'latest-watchlist', 'Latest in Watchlist', $isVisible, $isExtended);
    }

    public static function createPersonalRatingDistribution(bool $isVisible = true, bool $isExtended = false) : self
    {
        return self::create(13, 'personal-rating-distribution', 'Rating distribution', $isVisible, $isExtended);
    }

    private static function create(int $id, string $key, string $name, bool $isVisible, bool $isExtended) : self
    {
        return new self($id, $key, $name, $isVisible, $isExtended);
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getKey() : string
    {
        return $this->key;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function isExtended() : bool
    {
        return $this->isExtended;
    }

    public function isLastPlays() : bool
    {
        return $this->getId() === self::createLastPlays()->getId();
    }

    public function isLastPlaysCinema() : bool
    {
        return $this->getId() === self::createLastPlaysCinema()->getId();
    }

    public function isMostWatchedActors() : bool
    {
        return $this->getId() === self::createMostWatchedActors()->getId();
    }

    public function isMostWatchedActresses() : bool
    {
        return $this->getId() === self::createMostWatchedActresses()->getId();
    }

    public function isMostWatchedDirectors() : bool
    {
        return $this->getId() === self::createMostWatchedDirectors()->getId();
    }

    public function isMostWatchedGenres() : bool
    {
        return $this->getId() === self::createMostWatchedGenres()->getId();
    }

    public function isMostWatchedLanguages() : bool
    {
        return $this->getId() === self::createMostWatchedLanguages()->getId();
    }

    public function isMostWatchedProductionCompanies() : bool
    {
        return $this->getId() === self::createMostWatchedProductionCompanies()->getId();
    }

    public function isMostWatchedProductionCountries() : bool
    {
        return $this->getId() === self::createMostWatchedProductionCountries()->getId();
    }

    public function isMostWatchedReleaseYears() : bool
    {
        return $this->getId() === self::createMostWatchedReleaseYears()->getId();
    }

    public function isPersonalRatingDistribution() : bool
    {
        return $this->getId() === self::createPersonalRatingDistribution()->getId();
    }

    public function isTopLocations() : bool
    {
        return $this->getId() === self::createTopLocations()->getId();
    }

    public function isVisible() : bool
    {
        return $this->isVisible;
    }

    public function isWatchlist() : bool
    {
        return $this->getId() === self::createWatchlist()->getId();
    }
}
