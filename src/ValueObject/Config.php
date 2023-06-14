<?php declare(strict_types=1);

namespace Movary\ValueObject;

use Movary\Util\File;
use Movary\ValueObject\Exception\ConfigKeyNotSetException;

class Config
{
    public function __construct(
        private readonly File $fileUtil,
        private readonly array $config,
    ) {
    }

    /** @throws ConfigKeyNotSetException */
    public function getAsBool(string $key, ?bool $fallbackValue = null) : bool
    {
        try {
            return (bool)$this->get($key);
        } catch (ConfigKeyNotSetException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    /** @throws ConfigKeyNotSetException */
    public function getAsInt(string $key, ?int $fallbackValue = null) : int
    {
        try {
            return (int)$this->get($key);
        } catch (ConfigKeyNotSetException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    /** @throws ConfigKeyNotSetException */
    public function getAsString(string $key, ?string $fallbackValue = null) : string
    {
        try {
            return (string)$this->get($key);
        } catch (ConfigKeyNotSetException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    /** @throws ConfigKeyNotSetException */
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

        throw ConfigKeyNotSetException::create($key);
    }
}
