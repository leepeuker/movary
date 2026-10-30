<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use InvalidArgumentException;
use Movary\Domain\Country\CountryApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\DateFormat;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\MovieTitleDisplay;

class UserPreferencesRequestMapper
{
    private const array BOOLEAN_FIELDS = [
        'watchlistAutomaticRemovalEnabled',
        'displayCharacterNames',
        'displayTmdbRatings',
        'displayImdbRatings',
    ];

    private const array WRITABLE_FIELDS = [
        'username',
        'privacyLevel',
        'dateFormatId',
        'movieTitleDisplay',
        'country',
        ...self::BOOLEAN_FIELDS,
    ];

    public function __construct(
        private readonly RequestMapper $requestMapper,
        private readonly CountryApi $countryApi,
    ) {
    }

    /** @return array<string, bool|int|string|null> */
    public function mapUpdate(Request $request) : array
    {
        $data = $this->requestMapper->mapJsonObject($request);
        if ($data === []) {
            throw InvalidApiRequest::invalidField('preferences', 'must contain at least one supported field');
        }

        $preferences = [];
        foreach ($data as $field => $value) {
            if (in_array($field, self::WRITABLE_FIELDS, true) === false) {
                throw InvalidApiRequest::invalidField($field, 'is not writable');
            }

            $preferences[$field] = $this->mapField($field, $value);
        }

        return $preferences;
    }

    private function mapField(string $field, mixed $value) : bool|int|string|null
    {
        if (in_array($field, self::BOOLEAN_FIELDS, true) === true) {
            if (is_bool($value) === false) {
                throw InvalidApiRequest::invalidField($field, 'must be a boolean');
            }

            return $value;
        }

        return match ($field) {
            'username' => $this->mapUsername($value),
            'privacyLevel' => $this->mapPrivacyLevel($value),
            'dateFormatId' => $this->mapDateFormatId($value),
            'movieTitleDisplay' => $this->mapMovieTitleDisplay($value),
            'country' => $this->mapCountry($value),
            default => throw InvalidApiRequest::invalidField($field, 'is not writable'),
        };
    }

    private function mapUsername(mixed $value) : string
    {
        if (is_string($value) === false) {
            throw InvalidApiRequest::invalidField('username', 'must be a string');
        }

        return $value;
    }

    private function mapPrivacyLevel(mixed $value) : int
    {
        if (is_int($value) === false || in_array($value, [0, 1, 2], true) === false) {
            throw InvalidApiRequest::invalidField('privacyLevel', 'must be 0, 1, or 2');
        }

        return $value;
    }

    private function mapDateFormatId(mixed $value) : int
    {
        if (is_int($value) === false || array_key_exists($value, DateFormat::getFormats()) === false) {
            throw InvalidApiRequest::invalidField('dateFormatId', 'must be a supported date format id');
        }

        return $value;
    }

    private function mapMovieTitleDisplay(mixed $value) : string
    {
        if (is_string($value) === false) {
            throw InvalidApiRequest::invalidField('movieTitleDisplay', 'must be localized, original, or both');
        }

        try {
            return (string)MovieTitleDisplay::createFromString($value);
        } catch (InvalidArgumentException) {
            throw InvalidApiRequest::invalidField('movieTitleDisplay', 'must be localized, original, or both');
        }
    }

    private function mapCountry(mixed $value) : ?string
    {
        if ($value !== null && (is_string($value) === false || array_key_exists($value, $this->countryApi->getIso31661ToNameMap()) === false)) {
            throw InvalidApiRequest::invalidField('country', 'must be a supported country code or null');
        }

        return $value;
    }
}
