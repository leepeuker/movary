<?php declare(strict_types=1);

namespace Movary\Service;

use InvalidArgumentException;
use Movary\ValueObject\Config;

class ApplicationSecret
{
    private readonly string $secret;

    public function __construct(Config $config)
    {
        $encodedSecret = $config->getAsString('APPLICATION_SECRET');
        if (preg_match('/\A[0-9a-f]{64}\z/Di', $encodedSecret) !== 1) {
            throw new InvalidArgumentException(
                'APPLICATION_SECRET must contain exactly 64 hexadecimal characters.',
            );
        }

        $secret = hex2bin($encodedSecret);
        if ($secret === false) {
            throw new InvalidArgumentException('APPLICATION_SECRET is not valid hexadecimal data.');
        }

        $this->secret = $secret;
    }

    public function deriveKey(string $purpose) : string
    {
        return hash_hkdf('sha256', $this->secret, 32, $purpose);
    }
}
