<?php declare(strict_types=1);

namespace Movary\ValueObject;

use Cron\CronExpression;
use InvalidArgumentException;
use JsonSerializable;

class JobScheduleCronExpression implements JsonSerializable
{
    private readonly string $expression;

    private function __construct(string $expression)
    {
        $parts = preg_split('/\s+/', trim($expression));
        if ($parts === false || count($parts) !== 5) {
            throw new InvalidArgumentException('Cron expression must contain five fields.');
        }

        $this->expression = implode(' ', $parts);
        if (CronExpression::isValidExpression($this->expression) === false) {
            throw new InvalidArgumentException('Cron expression is invalid.');
        }
    }

    public static function createFromString(string $expression) : self
    {
        return new self($expression);
    }

    public function __toString() : string
    {
        return $this->expression;
    }

    public function calculateNextRun(DateTime $currentDateTime) : DateTime
    {
        $nextRun = (new CronExpression($this->expression))->getNextRunDate(
            (string)$currentDateTime,
            timeZone: DateTime::DEFAULT_TIME_ZONE,
        );

        return DateTime::createFromString($nextRun->format('Y-m-d H:i:s'));
    }

    public function jsonSerialize() : string
    {
        return $this->expression;
    }
}
