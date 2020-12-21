<?php

namespace PhoneBurnerTest\Enum\Fixtures;

use PhoneBurner\Enum\Enum;

/**
 * @method static RgbColor RED()
 * @method static RgbColor GREEN()
 * @method static RgbColor BLUE()
 */
final class RgbColor extends Enum
{
    public static function getValues(): array
    {
        return [
            'RED' => 'red',
            'GREEN' => 'green',
            'BLUE' => 'blue',
        ];
    }
}
