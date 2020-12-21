<?php

namespace PhoneBurnerTest\Enum\Fixtures;

use PhoneBurner\Enum\Enum;

/**
 * @method static Nonscalar CALLBACK()
 * @method static Nonscalar OBJECT()
 * @method static Nonscalar ARRAY()
 * @method static Nonscalar NULL()
 */
final class Nonscalar extends Enum
{
    public static function getValues(): array
    {
        return [
            'CALLBACK' => fn() => 'red',
            'OBJECT' => new \stdClass(),
            'ARRAY' => ['sage', 'emerald', 'pickle', 'lime'],
            'NULL' => null,
        ];
    }
}
