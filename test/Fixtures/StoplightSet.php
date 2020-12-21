<?php

namespace PhoneBurnerTest\Enum\Fixtures;

use PhoneBurner\Enum\Enum;
use PhoneBurner\Enum\EnumSet;

class StoplightSet extends EnumSet
{
    public static function set(Enum ...$enums): EnumSet
    {
        return new static(Stoplight::class, ...$enums);
    }
}
