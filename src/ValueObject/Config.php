<?php declare(strict_types=1);

namespace Movary\ValueObject;

use Movary\Util\File;

class Config
{
    public function __construct(
        private readonly File $fileUtil,
        private readonly array $config,
    ) {
    }

    public function getAsBool(string $parameter, ?bool $fallbackValue = null) : bool
    {
        try {
            return (bool)$this->get($parameter);
        } catch (\RuntimeException $e) {
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
        } catch (\RuntimeException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    public function getAsString(string $parameter, ?string $fallbackValue = null) : string
    {
        try {
            return (string)$this->get($parameter);
        } catch (\RuntimeException $e) {
            if ($fallbackValue === null) {
                throw $e;
            }

            return $fallbackValue;
        }
    }

    private function get(string $key) : mixed
    {
        if (isset($this->config[$key]) === true) {
            return $this->config[$key];
        }

        if (isset($this->config[$key . '_FILE']) === true) {
            $secretFile = $this->get($key . '_FILE');

            if ($this->fileUtil->fileExists($secretFile) === true) {
                return $this->fileUtil->readFile($secretFile);
            }
        }

        throw new \RuntimeException('Config key does not exist: ' . $key);
    }
}
