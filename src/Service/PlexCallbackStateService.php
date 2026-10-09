<?php declare(strict_types=1);

namespace Movary\Service;

class PlexCallbackStateService
{
    private const int LIFETIME_IN_SECONDS = 900;

    private const string KEY_DERIVATION_INFO = 'movary:plex-callback:v1';

    private const int NONCE_BYTES = 16;

    private const string STATE_VERSION = 'v1';

    private readonly string $signingKey;

    public function __construct(
        ApplicationSecret $applicationSecret,
        private readonly ?int $currentTimestamp = null,
    ) {
        $this->signingKey = $applicationSecret->deriveKey(self::KEY_DERIVATION_INFO);
    }

    public function create(string $plexPinId, string $plexCode, string $authenticationToken) : string
    {
        $expiresAt = $this->getCurrentTimestamp() + self::LIFETIME_IN_SECONDS;
        $nonce = bin2hex(random_bytes(self::NONCE_BYTES));
        $signature = $this->createSignature(
            (string)$expiresAt,
            $nonce,
            $plexPinId,
            $plexCode,
            $authenticationToken,
        );

        return implode('.', [self::STATE_VERSION, $expiresAt, $nonce, $signature]);
    }

    public function isValid(
        ?string $state,
        string $plexPinId,
        string $plexCode,
        ?string $authenticationToken,
    ) : bool {
        if ($state === null || $authenticationToken === null || strlen($state) > 128) {
            return false;
        }

        if (preg_match('/\Av1\.([0-9]{1,12})\.([0-9a-f]{32})\.([0-9a-f]{64})\z/D', $state, $matches) !== 1) {
            return false;
        }

        if ((int)$matches[1] <= $this->getCurrentTimestamp()) {
            return false;
        }

        $expectedSignature = $this->createSignature(
            $matches[1],
            $matches[2],
            $plexPinId,
            $plexCode,
            $authenticationToken,
        );

        return hash_equals($expectedSignature, $matches[3]);
    }

    private function createSignature(
        string $expiresAt,
        string $nonce,
        string $plexPinId,
        string $plexCode,
        string $authenticationToken,
    ) : string {
        $signatureInput = $this->encodeFields([
            self::STATE_VERSION,
            $expiresAt,
            $nonce,
            $plexPinId,
            hash('sha256', $plexCode),
            hash('sha256', $authenticationToken),
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

    private function getCurrentTimestamp() : int
    {
        return $this->currentTimestamp ?? time();
    }
}
