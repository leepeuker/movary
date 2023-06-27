<?php declare(strict_types=1);

namespace Movary\ValueObject;

use RuntimeException;

class JobStatus implements \JsonSerializable
{
    private const STATUS_DONE = 'done';

    private const STATUS_FAILED = 'failed';

    private const STATUS_IN_PROGRESS = 'in progress';

    private const STATUS_WAITING = 'waiting';

    private function __construct(private readonly string $status)
    {
        if (in_array($this->status, [
                self::STATUS_DONE,
                self::STATUS_IN_PROGRESS,
                self::STATUS_WAITING,
                self::STATUS_FAILED
            ]) === false) {
            throw new RuntimeException('Not supported job status: ' . $this->status);
        }
    }

    public static function createDone() : self
    {
        return new self(self::STATUS_DONE);
    }

    public static function createFailed() : self
    {
        return new self(self::STATUS_FAILED);
    }

    public static function createFromString(string $status) : self
    {
        return new self($status);
    }

    public static function createInProgress() : self
    {
        return new self(self::STATUS_IN_PROGRESS);
    }

    public static function createWaiting() : self
    {
        return new self(self::STATUS_WAITING);
    }

    public function __toString() : string
    {
        return $this->status;
    }

    public function jsonSerialize() : string
    {
        return $this->status;
    }
}
