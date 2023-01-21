<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject;

class TraktCredentials
{
    private function __construct(
        private readonly string $username,
        private readonly string $clientId,
    ) {
    }

    public static function create(string $username, string $clientId) : self
    {
        return new self($username, $clientId);
    }

    public function getClientId() : string
    {
        return $this->clientId;
    }

    public function getUsername() : string
    {
        return $this->username;
    }
}
