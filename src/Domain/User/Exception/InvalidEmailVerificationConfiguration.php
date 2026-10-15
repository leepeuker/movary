<?php declare(strict_types=1);

namespace Movary\Domain\User\Exception;

use RuntimeException;

class InvalidEmailVerificationConfiguration extends RuntimeException
{
    public static function create() : self
    {
        return new self(
            'EMAIL_VERIFICATION_REQUIRED requires enabled email support, valid SMTP settings, and APPLICATION_URL.',
        );
    }
}
