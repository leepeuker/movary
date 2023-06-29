<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

class MovaryApplicationUrlNotSet extends \RuntimeException
{
    public static function create() : self
    {
        return new self('Movary application url is not set');
    }
}
