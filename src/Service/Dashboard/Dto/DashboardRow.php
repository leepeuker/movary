<?php declare(strict_types=1);

namespace Movary\Service\Dashboard\Dto;

class DashboardRow
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly bool $isExtended,
    ) {
    }

    public static function createLastPlays(bool $isExtended = true) : self
    {
        return self::create(0, 'Last Plays', $isExtended);
    }

    public static function createMostWatchedActors(bool $isExtended = false) : self
    {
        return self::create(1, 'Most watched Actors', $isExtended);
    }

    public static function createMostWatchedActresses(bool $isExtended = false) : self
    {
        return self::create(2, 'Most watched Actresses', $isExtended);
    }

    public static function createMostWatchedDirectors(bool $isExtended = false) : self
    {
        return self::create(3, 'Most watched Directors', $isExtended);
    }

    public static function createMostWatchedGenres(bool $isExtended = false) : self
    {
        return self::create(4, 'Most watched Genres', $isExtended);
    }

    public static function createMostWatchedLanguages(bool $isExtended = false) : self
    {
        return self::create(8, 'Most watched Languages', $isExtended);
    }

    public static function createMostWatchedProductionCompanies(bool $isExtended = false) : self
    {
        return self::create(6, 'Most watched Production Companies', $isExtended);
    }

    public static function createMostWatchedReleaseYears(bool $isExtended = false) : self
    {
        return self::create(7, 'Most watched Release Years', $isExtended);
    }

    private static function create(int $id, string $name, bool $isExtended) : self
    {
        return new self($id, $name, $isExtended);
    }

    public function getId() : int
    {
        return $this->id;
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
        return $this->getId() === self::createMostWatchedDirectors()->getId();
    }

    public function isMostWatchedLanguages() : bool
    {
        return $this->getId() === self::createMostWatchedLanguages()->getId();
    }

    public function isMostWatchedProductionCompanies() : bool
    {
        return $this->getId() === self::createMostWatchedProductionCompanies()->getId();
    }

    public function isMostWatchedReleaseYears() : bool
    {
        return $this->getId() === self::createMostWatchedReleaseYears()->getId();
    }
}
