<?php declare(strict_types=1);

namespace Movary\Domain\User\ValueObject;

class AuthenticatedUser
{
    private function __construct(
        private readonly int $userId,
        private readonly CredentialType $credentialType,
    ) {
    }

    public static function create(int $userId, CredentialType $credentialType) : self
    {
        return new self($userId, $credentialType);
    }

    public function getCredentialType() : CredentialType
    {
        return $this->credentialType;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }
}
