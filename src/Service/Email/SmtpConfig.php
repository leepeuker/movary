<?php declare(strict_types=1);

namespace Movary\Service\Email;

class SmtpConfig
{
    private function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $fromAddress,
        private readonly ?string $encryption,
        private readonly bool $withAuthentication,
        private readonly ?string $user,
        private readonly ?string $password,
    ) {
    }

    public static function create(
        string $host,
        int $port,
        string $fromAddress,
        ?string $encryption,
        bool $withAuthentication,
        ?string $user,
        ?string $password,
    ) : self {
        return new self($host, $port, $fromAddress, $encryption, $withAuthentication, $user, $password);
    }

    public function getEncryption() : ?string
    {
        return $this->encryption;
    }

    public function getFromAddress() : string
    {
        return $this->fromAddress;
    }

    public function getHost() : string
    {
        return $this->host;
    }

    public function getPassword() : ?string
    {
        return $this->password;
    }

    public function getPort() : int
    {
        return $this->port;
    }

    public function getUser() : ?string
    {
        return $this->user;
    }

    public function isWithAuthentication() : bool
    {
        return $this->withAuthentication;
    }
}
