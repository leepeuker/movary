<?php declare(strict_types=1);

namespace Movary\Service;

class CsrfTokenService
{
    private const string ANONYMOUS_AUTHENTICATION_CONTEXT = 'anonymous';

    private const string KEY_DERIVATION_INFO = 'movary:csrf:v1';

    private const int NONCE_BYTES = 32;

    private const string TOKEN_VERSION = 'v1';

    private readonly string $signingKey;

    public function __construct(ApplicationSecret $applicationSecret)
    {
        $this->signingKey = $applicationSecret->deriveKey(self::KEY_DERIVATION_INFO);
    }

    public function create(?string $authenticationToken) : string
    {
        $nonce = bin2hex(random_bytes(self::NONCE_BYTES));
        $signature = $this->createSignature($nonce, $authenticationToken);

        return implode('.', [self::TOKEN_VERSION, $nonce, $signature]);
    }

    public function isValid(
        ?string $cookieToken,
        ?string $submittedToken,
        ?string $authenticationToken,
    ) : bool {
        if ($cookieToken === null || $submittedToken === null) {
            return false;
        }

        if (strlen($cookieToken) !== 132 || strlen($submittedToken) !== 132) {
            return false;
        }

        if (hash_equals($cookieToken, $submittedToken) === false) {
            return false;
        }

        if (preg_match('/\Av1\.([0-9a-f]{64})\.([0-9a-f]{64})\z/D', $cookieToken, $matches) !== 1) {
            return false;
        }

        $expectedSignature = $this->createSignature($matches[1], $authenticationToken);

        return hash_equals($expectedSignature, $matches[2]);
    }

    private function createSignature(string $nonce, ?string $authenticationToken) : string
    {
        $authenticationContext = self::ANONYMOUS_AUTHENTICATION_CONTEXT;
        if ($authenticationToken !== null && $authenticationToken !== '') {
            $authenticationContext = 'authenticated:' . hash('sha256', $authenticationToken);
        }

        $signatureInput = $this->encodeFields([
            self::TOKEN_VERSION,
            $nonce,
            $authenticationContext,
        ]);

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
