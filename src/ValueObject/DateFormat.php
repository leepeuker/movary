<?php declare(strict_types=1);

namespace Movary\ValueObject;

class DateFormat
{
    private const FORMATS = [
        [
            self::KEY_PHP => 'y-m-d',
            self::KEY_JAVASCRIPT => 'yy-mm-dd',
        ],
        [
            self::KEY_PHP => 'Y-m-d',
            self::KEY_JAVASCRIPT => 'yyyy-mm-dd',
        ],
        [
            self::KEY_PHP => 'd.m.y',
            self::KEY_JAVASCRIPT => 'dd.mm.yy',
        ],
        [
            self::KEY_PHP => 'd.m.Y',
            self::KEY_JAVASCRIPT => 'dd.mm.yyyy',
        ],
    ];

    private const KEY_JAVASCRIPT = 'javascript';

    private const KEY_PHP = 'php';

    public static function getFormats() : array
    {
        return self::FORMATS;
    }

    public static function getJavascriptByOffset(int $offset) : string
    {
        if (isset(self::FORMATS[$offset]) === false) {
            throw new \RuntimeException('Offset does not exist: ' . $offset);
        }

        return self::FORMATS[$offset][self::KEY_JAVASCRIPT];
    }

    public static function getJavascriptDefault() : string
    {
        return self::FORMATS[0][self::KEY_JAVASCRIPT];
    }

    public static function getPhpByOffset(int $offset) : string
    {
        if (isset(self::FORMATS[$offset]) === false) {
            throw new \RuntimeException('Offset does not exist: ' . $offset);
        }

        return self::FORMATS[$offset][self::KEY_PHP];
    }

    public static function getPhpDefault() : string
    {
        return self::FORMATS[0][self::KEY_PHP];
    }
}
