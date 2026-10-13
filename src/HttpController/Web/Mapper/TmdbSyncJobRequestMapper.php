<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use InvalidArgumentException;
use Movary\JobQueue\TmdbSyncJobParameters;
use Movary\ValueObject\Http\Request;

class TmdbSyncJobRequestMapper
{
    public function map(Request $request) : TmdbSyncJobParameters
    {
        $parameters = $request->getPostParameters();

        return TmdbSyncJobParameters::create(
            $this->mapOptionalPositiveInteger($parameters['maxAgeInHours'] ?? null, 'Maximum age'),
            $this->mapOptionalPositiveInteger($parameters['threshold'] ?? null, 'Maximum records'),
            $this->mapIds($parameters['ids'] ?? null),
            $this->mapBoolean($parameters['onlyNeverSynced'] ?? null, 'Never synced only'),
        );
    }

    public function isUnboundedSyncConfirmed(Request $request) : bool
    {
        return $this->mapBoolean(
            $request->getPostParameters()['confirmUnbounded'] ?? null,
            'Unbounded sync confirmation',
        );
    }

    private function mapBoolean(mixed $value, string $name) : bool
    {
        if ($value === null) {
            return false;
        }
        if ($value === '1' || $value === 1 || $value === true) {
            return true;
        }

        throw new InvalidArgumentException($name . ' must be a boolean.');
    }

    private function mapIds(mixed $value) : array
    {
        if ($value === null || $value === '' || (is_string($value) === true && trim($value) === '')) {
            return [];
        }
        if (is_string($value) === false) {
            throw new InvalidArgumentException('IDs must be a comma-separated list of positive integers.');
        }

        $ids = [];
        foreach (explode(',', $value) as $id) {
            $ids[] = $this->mapPositiveInteger(trim($id), 'IDs');
        }

        return $ids;
    }

    private function mapOptionalPositiveInteger(mixed $value, string $name) : ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->mapPositiveInteger($value, $name);
    }

    private function mapPositiveInteger(mixed $value, string $name) : int
    {
        if (is_int($value) === true && $value > 0) {
            return $value;
        }
        if (is_string($value) === false || preg_match('/^[1-9][0-9]*$/D', $value) !== 1) {
            throw new InvalidArgumentException($name . ' must contain only positive integers.');
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($integer === false) {
            throw new InvalidArgumentException($name . ' must contain only positive integers.');
        }

        return $integer;
    }
}
