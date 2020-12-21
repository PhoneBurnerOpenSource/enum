<?php

namespace PhoneBurnerTest\Enum;

use InvalidArgumentException;
use PhoneBurner\Enum\EnumSet;
use PhoneBurner\Enum\Exception\ReadOnly;
use PhoneBurnerTest\Enum\Fixtures\ErrorLevel;
use PhoneBurnerTest\Enum\Fixtures\Nonscalar;
use PhoneBurnerTest\Enum\Fixtures\RgbColor;
use PhoneBurnerTest\Enum\Fixtures\Scalar;
use PhoneBurnerTest\Enum\Fixtures\Stoplight;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class EnumTest extends TestCase
{
    /**
     * @test
     * @testWith ["red"]
     *           ["yellow"]
     *           ["green"]
     */
    public function enums_can_be_constructed_by_magic_method(string $value): void
    {
        $key = strtoupper($value);
        $enum = Stoplight::$key();
        self::assertInstanceOf(Stoplight::class, $enum);
        self::assertSame($value, $enum->getValue());
    }

    /**
     * @test
     * @testWith    ["ALL", 32767]
     *              ["ERROR", 1]
     *              ["DEPRECATED", 8192]
     */
    public function init_constructs_enums_by_key(string $key, int $value): void
    {
        $enum = ErrorLevel::init($key);
        self::assertInstanceOf(ErrorLevel::class, $enum);
        self::assertSame($enum->getValue(), $value);
        self::assertSame(ErrorLevel::init($key), ErrorLevel::make($value));
    }

    /**
     * @test
     * @testWith    ["ORANGE"]
     *              ["32767"]
     */
    public function init_catches_invalid_enum_values(string $bad_key): void
    {
        $this->expectException(UnexpectedValueException::class);
        ErrorLevel::init($bad_key);
    }

    /**
     * @test
     */
    public function enums_can_be_compared_by_value_equality(): void
    {
        self::assertEquals(Stoplight::RED(), Stoplight::RED());
        self::assertNotEquals(Stoplight::RED(), Stoplight::GREEN());
        self::assertNotEquals(Stoplight::RED(), Stoplight::YELLOW());
        self::assertNotEquals(Stoplight::RED(), RgbColor::RED());
    }

    /**
     * @test
     * @testWith ["STRING", "hello, world!"]
     *           ["INTEGER", 42]
     *           ["FLOAT", 3.1415926535898]
     *           ["TRUE", true]
     *           ["FALSE", false]
     */
    public function enums_can_hold_scalar_values(string $key, $value): void
    {
        self::assertSame($value, Scalar::$key()->getValue());
        self::assertEquals(Scalar::$key(), Scalar::$key());
    }

    /**
     * @test
     */
    public function enums_are_cached(): void
    {
        $red = RgbColor::RED();
        self::assertSame($red, RgbColor::RED());
        self::assertSame($red, RgbColor::make('red'));
        self::assertSame(RgbColor::RED(), RgbColor::RED());

        self::assertNotSame(RgbColor::RED(), Stoplight::RED());
    }

    /**
     * @test
     * @testWith ["STRING", "hello, world!"]
     *           ["INTEGER", 42]
     *           ["FLOAT", 3.1415926535898]
     *           ["TRUE", true]
     *           ["FALSE", false]
     */
    public function jsonSerialize_returns_the_underlying_scalar_value(string $key, $value): void
    {
        self::assertSame($value, Scalar::$key()->jsonSerialize());
    }

    /**
     * @test
     * @testWith ["STRING", "hello, world!"]
     *           ["INTEGER", "42"]
     *           ["FLOAT", "3.1415926535898"]
     *           ["TRUE", "1"]
     *           ["FALSE", ""]
     */
    public function toString_casts_scalar_values_to_strings(string $key, string $string): void
    {
        self::assertSame($string, (string)Scalar::$key());
    }

    /**
     * @test
     */
    public function constructor_catches_invalid_keys(): void
    {
        $this->expectException(UnexpectedValueException::class);
        Stoplight::ORANGE();
    }

    /**
     * @test
     * @testWith ["CALLBACK"]
     *           ["OBJECT"]
     *           ["ARRAY"]
     *           ["NULL"]
     */
    public function constructor_catches_nonscalar_values(string $key): void
    {
        $this->expectException(\DomainException::class);
        Nonscalar::$key();
    }

    /**
     * @test
     */
    public function make_constructs_enum_from_value(): void
    {
        $enum = Stoplight::make('red');

        self::assertEquals(Stoplight::RED(), $enum);
        self::assertSame('red', $enum->getValue());
    }

    /**
     * @test
     */
    public function make_catches_invalid_enum_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Stoplight::make('orange');
    }

    /**
     * @test
     */
    public function enums_can_be_completely_enumerated(): void
    {
        $enums = Stoplight::getEnumeration();

        self::assertCount(3, $enums);
        self::assertContainsOnlyInstancesOf(Stoplight::class, $enums);
        self::assertEquals([
            'RED' => Stoplight::RED(),
            'YELLOW' => Stoplight::YELLOW(),
            'GREEN' => Stoplight::GREEN(),
        ], $enums);
    }

    /**
     * @test
     */
    public function isValidKey_checks_the_existence_of_a_key(): void
    {
        self::assertTrue(Stoplight::isValidKey('RED'));
        self::assertTrue(Stoplight::isValidKey('YELLOW'));
        self::assertTrue(Stoplight::isValidKey('GREEN'));
        self::assertFalse(Stoplight::isValidKey('red'));
        self::assertFalse(Stoplight::isValidKey('ORANGE'));
    }

    /**
     * @test
     */
    public function isValidValue_checks_the_existence_of_a_value(): void
    {
        self::assertTrue(Stoplight::isValidValue('red'));
        self::assertTrue(Stoplight::isValidValue('yellow'));
        self::assertTrue(Stoplight::isValidValue('green'));
        self::assertFalse(Stoplight::isValidValue('RED'));
        self::assertFalse(Stoplight::isValidValue('orange'));
    }

    /**
     * @test
     */
    public function isValidValue_checks_the_membership_by_identity_or_equality(): void
    {
        self::assertTrue(ErrorLevel::isValidValue(1 << 13));
        self::assertTrue(ErrorLevel::isValidValue(4096));
        self::assertFalse(ErrorLevel::isValidValue('4096'));
        self::assertTrue(ErrorLevel::isValidValue('4096', false));
    }

    /**
     * @test
     */
    public function enums_are_immutable(): void
    {
        $enum = Stoplight::GREEN();
        $this->expectException(ReadOnly::class);
        $enum->mutable_property = 'red';
    }

    /**
     * @test
     */
    public function is_strictly_compares_enums(): void
    {
        self::assertTrue(Stoplight::GREEN()->is(Stoplight::GREEN()));
        self::assertTrue(Stoplight::GREEN()->is(Stoplight::make('green')));
        self::assertFalse(Stoplight::GREEN()->is('green'));
        self::assertFalse(Stoplight::GREEN()->is('red'));
        self::assertFalse(Stoplight::GREEN()->is(Stoplight::RED()));
        self::assertFalse(Stoplight::GREEN()->is(RgbColor::GREEN()));
        self::assertFalse(Stoplight::GREEN()->is(RgbColor::RED()));
    }

    /**
     * @test
     */
    public function isNot_strictly_compares_enums(): void
    {
        self::assertFalse(Stoplight::GREEN()->isNot(Stoplight::GREEN()));
        self::assertFalse(Stoplight::GREEN()->isNot(Stoplight::make('green')));
        self::assertTrue(Stoplight::GREEN()->isNot('green'));
        self::assertTrue(Stoplight::GREEN()->isNot('red'));
        self::assertTrue(Stoplight::GREEN()->isNot(Stoplight::RED()));
        self::assertTrue(Stoplight::GREEN()->isNot(RgbColor::GREEN()));
        self::assertTrue(Stoplight::GREEN()->isNot(RgbColor::RED()));
    }

    /**
     * @test
     */
    public function equals_loosely_compares_enums(): void
    {
        self::assertTrue(Stoplight::GREEN()->equals(Stoplight::GREEN()));
        self::assertTrue(Stoplight::GREEN()->equals(Stoplight::make('green')));
        self::assertTrue(Stoplight::GREEN()->equals('green'));
        self::assertFalse(Stoplight::GREEN()->equals('red'));
        self::assertFalse(Stoplight::GREEN()->equals(Stoplight::RED()));
        self::assertFalse(Stoplight::GREEN()->equals(RgbColor::GREEN()));
        self::assertFalse(Stoplight::GREEN()->equals(RgbColor::RED()));
    }

    /**
     * @test
     */
    public function equals_can_compare_values_by_identity_or_equality(): void
    {
        self::assertTrue(ErrorLevel::ALL()->equals(32767));
        self::assertFalse(ErrorLevel::ALL()->equals('32767'));
        self::assertTrue(ErrorLevel::ALL()->equals(32767, false));
    }

    /**
     * @test
     */
    public function notEquals_loosely_compares_enums(): void
    {
        self::assertFalse(Stoplight::GREEN()->notEquals(Stoplight::GREEN()));
        self::assertFalse(Stoplight::GREEN()->notEquals(Stoplight::make('green')));
        self::assertFalse(Stoplight::GREEN()->notEquals('green'));
        self::assertTrue(Stoplight::GREEN()->notEquals('red'));
        self::assertTrue(Stoplight::GREEN()->notEquals(Stoplight::RED()));
        self::assertTrue(Stoplight::GREEN()->notEquals(RgbColor::GREEN()));
        self::assertTrue(Stoplight::GREEN()->notEquals(RgbColor::RED()));
    }

    /**
     * @test
     */
    public function notEquals_can_compare_values_by_identity_or_equality(): void
    {
        self::assertFalse(ErrorLevel::ALL()->notEquals(32767));
        self::assertTrue(ErrorLevel::ALL()->notEquals('32767'));
        self::assertFalse(ErrorLevel::ALL()->notEquals(32767, false));
    }

    /**
     * @test
     */
    public function getKey_returns_the_string_key_for_instance_value(): void
    {
        self::assertSame('ALL', ErrorLevel::ALL()->getKey());
        self::assertSame('DEPRECATED', ErrorLevel::DEPRECATED()->getKey());
        self::assertSame('NOTICE', ErrorLevel::NOTICE()->getKey());
        self::assertSame('GREEN', Stoplight::GREEN()->getKey());
        self::assertSame('YELLOW', Stoplight::YELLOW()->getKey());
        self::assertSame('RED', Stoplight::RED()->getKey());
    }

    /**
     * @test
     */
    public function set_can_return_an_EnumSet_for_the_enum(): void
    {
        $set = ErrorLevel::set();

        self::assertInstanceOf(EnumSet::class, $set);
        self::assertTrue($set->isEmptySet());
        self::assertCount(0, $set);
        self::assertSame(ErrorLevel::class, $set->getEnumClass());
    }

    /**
     * @test
     */
    public function set_can_return_a_nonempty_EnumSet_for_the_enum(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::ALL(),
            ErrorLevel::ERROR(),
            ErrorLevel::PARSE(),
        ]);

        self::assertInstanceOf(EnumSet::class, $set);
        self::assertTrue($set->isNotEmptySet());
        self::assertCount(3, $set);
        self::assertSame(ErrorLevel::class, $set->getEnumClass());
    }
}
