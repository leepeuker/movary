<?php declare(strict_types=1);

namespace Movary\ValueObject;

class Config
{
    private array $data;

    public function __construct(string $configFile)
    {
        self::ensureFileIsReadable($configFile);

        $this->data = (array)parse_ini_file($configFile, true);
    }

    public static function createFromFile(string $configDir) : self
    {
        return new self($configDir);
    }

    /**
     * @throws \RuntimeException
     */
    private static function ensureFileIsReadable(string $file) : void
    {
        if (is_readable($file) === false) {
            throw new \RuntimeException('File is not readable: ' . $file);
        }
    }

    public function getAsArray(string $parameter) : array
    {
        return (array)$this->get($parameter);
    }

    public function getAsBool(string $parameter) : bool
    {
        return (bool)$this->get($parameter);
    }

    public function getAsFloat(string $parameter) : float
    {
        return (float)$this->get($parameter);
    }

    public function getAsInt(string $parameter) : int
    {
        return (int)$this->get($parameter);
    }

    public function getAsString(string $parameter) : string
    {
        return (string)$this->get($parameter);
    }

    private function ensureKeyExists(string $section, string $key) : void
    {
        if (isset($this->data[$section][$key]) === false) {
            throw new \OutOfBoundsException('Key does not exist: ' . $section . '.' . $key);
        }
    }

    private function ensureSectionExists(string $section) : void
    {
        if (isset($this->data[$section]) === false || is_array($this->data[$section]) === false) {
            throw new \OutOfBoundsException('Section does not exist: ' . $section);
        }
    }

    private function explode(string $parameter) : array
    {
        $pos = (int)strpos($parameter, '.');
        $section = substr($parameter, 0, $pos);

        $key = substr($parameter, $pos + 1);

        return [$section, $key];
    }

    /**
     * @return mixed
     */
    private function get(string $parameter)
    {
        [$section, $key] = $this->explode($parameter);

        $this->ensureSectionExists($section);
        $this->ensureKeyExists($section, $key);

        return $this->data[$section][$key];
    }
}
