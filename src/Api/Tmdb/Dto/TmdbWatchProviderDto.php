<?php

namespace Movary\Api\Tmdb\Dto;

use JsonSerializable;
use Movary\ValueObject\RelativeUrl;

class TmdbWatchProviderDto implements JsonSerializable
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly RelativeUrl $logo,
        private readonly int $displayPriority,
    ) {
    }

    public static function create(
        int $id,
        string $name,
        RelativeUrl $logo,
        int $displayPriority,
    ) : self {
        return new self($id, $name, $logo, $displayPriority);
    }

    public static function createFromArray(array $data) : self
    {
        return self::create(
            $data['provider_id'],
            $data['provider_name'],
            RelativeUrl::create($data['logo_path']),
            $data['display_priority'],
        );
    }

    public function getDisplayPriority() : int
    {
        return $this->displayPriority;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getLogo() : RelativeUrl
    {
        return $this->logo;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function jsonSerialize() : array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'displayPriority' => $this->displayPriority,
        ];
    }
}
