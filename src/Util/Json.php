<?php declare(strict_types=1);

namespace Movary\Util;

class Json
{
    public static function decode(string $json) : array
    {
        return (array)json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public static function encode(mixed $json) : string
    {
        return json_encode($json, JSON_THROW_ON_ERROR);
    }
}
