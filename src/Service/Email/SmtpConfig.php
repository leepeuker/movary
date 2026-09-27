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
        $host = trim($host);
        $fromAddress = trim($fromAddress);
        $encryption = $encryption === '' ? null : $encryption;

        if ($host === '') {
            throw new InvalidSmtpConfigException('SMTP host must be set.');
        }
        if ($port < 1 || $port > 65535) {
            throw new InvalidSmtpConfigException('SMTP port must be between 1 and 65535.');
        }
        if (filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidSmtpConfigException('SMTP from address must be a valid email address.');
        }
        if (in_array($encryption, [null, 'ssl', 'tls'], true) === false) {
            throw new InvalidSmtpConfigException('SMTP encryption must be ssl, tls, or empty.');
        }
        if ($withAuthentication === true && trim((string)$user) === '') {
            throw new InvalidSmtpConfigException('SMTP user must be set when authentication is enabled.');
        }
        if ($withAuthentication === true && (string)$password === '') {
            throw new InvalidSmtpConfigException('SMTP password must be set when authentication is enabled.');
        }

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
