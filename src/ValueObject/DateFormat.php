<?php declare(strict_types=1);

namespace Movary\ValueObject;

class DateFormat
{
    private const DEFAULT_ID = 0;

    private const FORMATS = [
        self::DEFAULT_ID => [
            self::KEY_PHP => 'y-m-d',
            self::KEY_JAVASCRIPT => 'yy-mm-dd',
        ],
        1 => [
            self::KEY_PHP => 'Y-m-d',
            self::KEY_JAVASCRIPT => 'yyyy-mm-dd',
        ],
        2 => [
            self::KEY_PHP => 'd.m.y',
            self::KEY_JAVASCRIPT => 'dd.mm.yy',
        ],
        3 => [
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

    public static function getJavascriptById(int $id) : string
    {
        if (isset(self::FORMATS[$id]) === false) {
            throw new \RuntimeException('Id does not exist: ' . $id);
        }

        return self::FORMATS[$id][self::KEY_JAVASCRIPT];
    }

    public static function getJavascriptDefault() : string
    {
        return self::getJavascriptById(self::DEFAULT_ID);
    }

    public static function getPhpById(int $offset) : string
    {
        if (isset(self::FORMATS[$offset]) === false) {
            throw new \RuntimeException('Id does not exist: ' . $offset);
        }

        return self::FORMATS[$offset][self::KEY_PHP];
    }

    public static function getPhpDefault() : string
    {
        return self::getPhpById(self::DEFAULT_ID);
    }
}
