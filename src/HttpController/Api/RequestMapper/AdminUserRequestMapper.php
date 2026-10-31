<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;

class AdminUserRequestMapper
{
    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    /** @return array{email: string, password: string, name: string, isAdmin: bool, isEnabled: bool, emailVerified: bool} */
    public function mapCreate(Request $request) : array
    {
        $data = $this->requestMapper->mapJsonObject($request);
        $this->validateFields($data, ['email', 'password', 'name', 'isAdmin', 'isEnabled', 'emailVerified']);

        return [
            'email' => $this->requiredEmail($data),
            'password' => $this->requiredString($data, 'password'),
            'name' => $this->requiredString($data, 'name'),
            'isAdmin' => $this->requiredBoolean($data, 'isAdmin'),
            'isEnabled' => $this->optionalBoolean($data, 'isEnabled', true),
            'emailVerified' => $this->optionalBoolean($data, 'emailVerified', true),
        ];
    }

    /** @return array{email?: string, password?: string, name?: string, isAdmin?: bool, isEnabled?: bool} */
    public function mapUpdate(Request $request) : array
    {
        $data = $this->requestMapper->mapJsonObject($request);
        $this->validateFields($data, ['email', 'password', 'name', 'isAdmin', 'isEnabled']);
        if ($data === []) {
            throw InvalidApiRequest::invalidField('user', 'must contain at least one writable field');
        }

        foreach ($data as $field => $value) {
            if ($field === 'email') {
                $this->requiredEmail($data);
            } elseif (in_array($field, ['password', 'name'], true) === true) {
                $this->requiredString($data, $field);
            } else {
                $this->requiredBoolean($data, $field);
            }
        }

        /** @var array{email?: string, password?: string, name?: string, isAdmin?: bool, isEnabled?: bool} $data */
        return $data;
    }

    /** @param array<string, mixed> $data
     * @param list<string> $allowedFields
     */
    private function validateFields(array $data, array $allowedFields) : void
    {
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields, true) === false) {
                throw InvalidApiRequest::invalidField($field, 'is not writable');
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function requiredString(array $data, string $field) : string
    {
        $value = $data[$field] ?? null;
        if (is_string($value) === false || $value === '') {
            throw InvalidApiRequest::invalidField($field, 'must be a non-empty string');
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function requiredEmail(array $data) : string
    {
        $email = $this->requiredString($data, 'email');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidApiRequest::invalidField('email', 'must be a valid email address');
        }

        return $email;
    }

    /** @param array<string, mixed> $data */
    private function requiredBoolean(array $data, string $field) : bool
    {
        $value = $data[$field] ?? null;
        if (is_bool($value) === false) {
            throw InvalidApiRequest::invalidField($field, 'must be a boolean');
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function optionalBoolean(array $data, string $field, bool $default) : bool
    {
        return array_key_exists($field, $data) === true ? $this->requiredBoolean($data, $field) : $default;
    }
}
