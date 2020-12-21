<?php

namespace PhoneBurnerTest\Enum\Fixtures;

use PhoneBurner\Enum\Enum;

/**
 * @method static ErrorLevel ERROR()
 * @method static ErrorLevel WARNING()
 * @method static ErrorLevel PARSE()
 * @method static ErrorLevel NOTICE()
 * @method static ErrorLevel CORE_ERROR()
 * @method static ErrorLevel CORE_WARNING()
 * @method static ErrorLevel COMPILE_ERROR()
 * @method static ErrorLevel COMPILE_WARNING()
 * @method static ErrorLevel USER_ERROR()
 * @method static ErrorLevel USER_WARNING()
 * @method static ErrorLevel USER_NOTICE()
 * @method static ErrorLevel STRICT()
 * @method static ErrorLevel RECOVERABLE_ERROR()
 * @method static ErrorLevel DEPRECATED()
 * @method static ErrorLevel USER_DEPRECATED()
 * @method static ErrorLevel ALL()
 */
final class ErrorLevel extends Enum
{
    public static function getValues(): array
    {
        return [
            'ERROR' => 1 << 0,
            'WARNING' => 1 << 1,
            'PARSE' => 1 << 2,
            'NOTICE' => 1 << 3,
            'CORE_ERROR' => 1 << 4,
            'CORE_WARNING' => 1 << 5,
            'COMPILE_ERROR' => 1 << 6,
            'COMPILE_WARNING' => 1 << 7,
            'USER_ERROR' => 1 << 8,
            'USER_WARNING' => 1 << 9,
            'USER_NOTICE' => 1 << 10,
            'STRICT' => 1 << 11,
            'RECOVERABLE_ERROR' => 1 << 12,
            'DEPRECATED' => 1 << 13,
            'USER_DEPRECATED' => 1 << 14,
            'ALL' => 32767,
        ];
    }
}
