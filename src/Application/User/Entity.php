<?php declare(strict_types=1);

namespace Movary\Application\User;

class Entity
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $passwordHash,
        private readonly bool $areCoreAccountChangesDisabled,
        private readonly int $dateFormatId,
        private readonly ?string $plexWebhookUuid,
        private readonly ?string $TraktUserName,
        private readonly ?string $TraktClientId,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            $data['name'],
            $data['password'],
            (bool)$data['core_account_changes_disabled'],
            $data['date_format_id'],
            $data['plex_webhook_uuid'],
            $data['trakt_user_name'],
            $data['trakt_client_id'],
        );
    }

    public function areCoreAccountChangesDisabled() : bool
    {
        return $this->areCoreAccountChangesDisabled;
    }

    public function getDateFormatId() : int
    {
        return $this->dateFormatId;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getPasswordHash() : string
    {
        return $this->passwordHash;
    }

    public function getPlexWebhookId() : ?string
    {
        return $this->plexWebhookUuid;
    }

    public function getTraktClientId() : ?string
    {
        return $this->TraktClientId;
    }

    public function getTraktUserName() : ?string
    {
        return $this->TraktUserName;
    }
}
