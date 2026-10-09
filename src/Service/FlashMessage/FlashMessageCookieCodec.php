<?php declare(strict_types=1);

namespace Movary\Service\FlashMessage;

use Movary\Service\ApplicationSecret;

class FlashMessageCookieCodec
{
    private const string KEY_DERIVATION_INFO = 'movary:flash-message:v1';

    private const int MAX_COOKIE_LENGTH = 80;

    private const string VERSION = 'v1';

    private readonly string $signingKey;

    public function __construct(ApplicationSecret $applicationSecret)
    {
        $this->signingKey = $applicationSecret->deriveKey(self::KEY_DERIVATION_INFO);
    }

    public function encode(FlashMessage $message, int $expires) : string
    {
        $expiresValue = (string)$expires;
        $signature = $this->createSignature($message, $expiresValue);

        return implode('.', [self::VERSION, $expiresValue, $signature]);
    }

    public function isValid(FlashMessage $message, string $cookieValue, int $now) : bool
    {
        if (strlen($cookieValue) > self::MAX_COOKIE_LENGTH) {
            return false;
        }

        if (preg_match('/\Av1\.([0-9]{1,12})\.([0-9a-f]{64})\z/D', $cookieValue, $matches) !== 1) {
            return false;
        }

        if ((int)$matches[1] <= $now) {
            return false;
        }

        return hash_equals($this->createSignature($message, $matches[1]), $matches[2]);
    }

    private function createSignature(FlashMessage $message, string $expires) : string
    {
        $signatureInput = $this->encodeFields([self::VERSION, $expires, $message->value]);

        return hash_hmac('sha256', $signatureInput, $this->signingKey);
    }

    /** @param array<string> $fields */
    private function encodeFields(array $fields) : string
    {
        $encoded = '';
        foreach ($fields as $field) {
            $encoded .= pack('N', strlen($field)) . $field;
        }

        return $encoded;
    }
}
