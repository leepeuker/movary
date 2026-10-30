<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;

class LocationRequestMapper
{
    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    /** @return array{name: string, isCinema: bool} */
    public function mapLocation(Request $request) : array
    {
        $data = $this->requestMapper->mapJsonObject($request);
        $name = $data['name'] ?? null;
        if (is_string($name) === false || trim($name) === '') {
            throw InvalidApiRequest::invalidField('name', 'must be a non-empty string');
        }

        return [
            'name' => $name,
            'isCinema' => $this->mapRequiredBoolean($data, 'isCinema'),
        ];
    }

    public function mapLocationsEnabled(Request $request) : bool
    {
        return $this->mapRequiredBoolean($this->requestMapper->mapJsonObject($request), 'locationsEnabled');
    }

    /** @param array<string, mixed> $data */
    private function mapRequiredBoolean(array $data, string $field) : bool
    {
        $value = $data[$field] ?? null;
        if (is_bool($value) === false) {
            throw InvalidApiRequest::invalidField($field, 'must be a boolean');
        }

        return $value;
    }
}
