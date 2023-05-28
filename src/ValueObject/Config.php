<?php declare(strict_types=1);

namespace Movary\ValueObject;

use OutOfBoundsException;

class Config
{
    public function __construct(private readonly array $config)
    {
    }

    public static function createFromEnv(array $additionalData = []) : self
    {
        $fpmEnvironment = $_ENV;
        $systemEnvironment = getenv();

        return new self(array_merge($fpmEnvironment, $systemEnvironment, $additionalData));
    }

    public function getAsBool(string $parameter, ?bool $fallbackValue = null) : bool
    {
        try {
            return (bool)$this->get($parameter);
        } catch (OutOfBoundsException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    public function getAsInt(string $parameter, ?int $fallbackValue = null) : int
    {
        try {
            return (int)$this->get($parameter);
        } catch (OutOfBoundsException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    public function getAsString(string $parameter, string $fallbackValue = null) : string
    {
        try {
            return (string)$this->get($parameter);
        } catch (OutOfBoundsException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    private function ensureKeyExists(string $key) : void
    {
        if (isset($this->config[$key]) === false) {
            throw new OutOfBoundsException('Key does not exist: ' . $key);
        }
    }

    private function get(string $key) : mixed
    {
        $this->ensureKeyExists($key);

        return $this->config[$key];
    }
}
