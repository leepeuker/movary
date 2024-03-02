<?php

namespace Movary\HttpController\Api\ValueObject;

use Movary\Domain\User\UserEntity;

class AuthenticationObject
{
    public const INT COOKIE_AUTHENTICATION = 1;
    public const INT HEADER_AUTHENTICATION = 2;
    public function __construct(
        public readonly string $token,
        public readonly int $authenticationMethod,
        public readonly UserEntity $user,
    ) { }

    public static function createAuthenticationObject(string $token, int $authenticationMethod, UserEntity $user) : self
    {
        return new self($token, $authenticationMethod, $user);
    }

    public function getToken() : string
    {
        return $this->token;
    }

    public function getAuthenticationMethod() : int
    {
        return $this->authenticationMethod;
    }

    public function getUser() : UserEntity
    {
        return $this->user;
    }

    public function hasCookieAuthentication() : bool
    {
        return $this->authenticationMethod === self::COOKIE_AUTHENTICATION;
    }

    public function hasHeaderAuthentication() : bool
    {
        return $this->authenticationMethod === self::HEADER_AUTHENTICATION;
    }
}
