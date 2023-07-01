<?php declare(strict_types=1);

namespace Movary\ValueObject;

use Movary\Util\File;
use Movary\ValueObject\Exception\ConfigNotSetException;

class Config
{
    public function __construct(
        private readonly File $fileUtil,
        private readonly array $config,
    ) {
    }

    /** @throws ConfigNotSetException */
    public function getAsBool(string $key, ?bool $fallbackValue = null) : bool
    {
        try {
            return (bool)$this->get($key);
        } catch (ConfigNotSetException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    /** @throws ConfigNotSetException */
    public function getAsInt(string $key, ?int $fallbackValue = null) : int
    {
        try {
            return (int)$this->get($key);
        } catch (ConfigNotSetException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    /** @throws ConfigNotSetException */
    public function getAsString(string $key, ?string $fallbackValue = null) : string
    {
        try {
            return (string)$this->get($key);
        } catch (ConfigNotSetException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    public function getAsStringNullable(string $key, ?string $fallbackValue = null) : ?string
    {
        try {
            return $this->getAsString($key, $fallbackValue);
        } catch (ConfigNotSetException) {
            return null;
        }
    }

    /** @throws ConfigNotSetException */
    private function get(string $key) : mixed
    {
        if (isset($this->config[$key]) === true) {
            return $this->config[$key];
        }

        if (isset($this->config[$key . '_FILE']) === true) {
            $secretFile = $this->get($key . '_FILE');

            if ($this->fileUtil->fileExists($secretFile) === true) {
                return trim($this->fileUtil->readFile($secretFile));
            }
        }

        throw ConfigNotSetException::create($key);
    }
}
