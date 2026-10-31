<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;

class ServerEmailRequestMapper
{
    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    /** @return array<string, mixed> */
    public function mapUpdate(Request $request) : array
    {
        $data = $this->requestMapper->mapJsonObject($request);
        if ($data === []) {
            throw InvalidApiRequest::invalidField('email', 'must contain at least one writable field');
        }

        foreach ($data as $field => $value) {
            match ($field) {
                'emailEnabled', 'smtpWithAuthentication' => $this->validateType($field, is_bool($value), 'a boolean'),
                'smtpPort' => $this->validateType($field, is_int($value), 'an integer'),
                'smtpHost', 'smtpFromAddress', 'smtpEncryption', 'smtpUser', 'smtpPassword' =>
                    $this->validateType($field, is_string($value), 'a string'),
                default => throw InvalidApiRequest::invalidField($field, 'is not writable'),
            };
        }

        return $data;
    }

    private function validateType(string $field, bool $valid, string $type) : void
    {
        if ($valid === false) {
            throw InvalidApiRequest::invalidField($field, 'must be ' . $type);
        }
    }
}
