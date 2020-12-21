<?php

namespace PhoneBurnerTest\Enum\Fixtures;

use PhoneBurner\Enum\Enum;

/**
 * @method static Stoplight RED()
 * @method static Stoplight YELLOW()
 * @method static Stoplight GREEN()
 */
final class Stoplight extends Enum
{
    public static function getValues(): array
    {
        return [
            'RED' => 'red',
            'YELLOW' => 'yellow',
            'GREEN' => 'green',
        ];
    }
}
