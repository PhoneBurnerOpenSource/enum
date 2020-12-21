<?php

namespace PhoneBurnerTest\Enum\Fixtures;

use PhoneBurner\Enum\Enum;

/**
 * @method static Scalar STRING()
 * @method static Scalar INTEGER()
 * @method static Scalar FLOAT()
 * @method static Scalar TRUE()
 * @method static Scalar FALSE()
 */
final class Scalar extends Enum
{
    public static function getValues(): array
    {
        return [
            'STRING' => 'hello, world!',
            'INTEGER' => 42,
            'FLOAT' => M_PI,
            'TRUE' => true,
            'FALSE' => false,
        ];
    }
}
