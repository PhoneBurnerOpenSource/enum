<?php

namespace PhoneBurner\Enum;

use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use PhoneBurner\Enum\Exception\ReadOnly;
use UnexpectedValueException;

/**
 * Base class for creating simple enumerated objects using a value object based
 * approach. This pattern should be used to encapsulate any well-defined set of
 * scalar values that share a specific context, for example, the states of a
 * traffic light, "red", "yellow", and "green". Doing so allows for immutability,
 * self-validation, and contextual type-hinting.
 *
 * Classes that extend the `Enum` class need only to implement a static `getValues`
 * method that returns an array of key-value pairs, where the value is the
 * actual scalar value we want to encapsulate in the enum, and the key is an
 * uppercase string that we can use to refer to the value, e.g.
 * `'APPROVED' => 23`, `'OH' => 'Ohio'` or `'GREEN' => 'green'`.  The enums are
 * then accessed by calling the key as a static method: `Status::APPROVED()`,
 * `UsState::OH()`, or `Stoplight::GREEN().  These magic static methods should
 * be declared in the enum class' docblock: "@method static Stoplight RED()"
 *
 * Notes:
 * 1. Enum classes *must* be declared final. Enums exist in exhaustively known,
 * limited, and discrete states. That is, by definition, an enum cannot be
 * extended.  Additionally, because this implementation of an enumerative
 * object is a pure value object with completely idempotent methods and no side
 * effects, it should never need to be mocked for testing.
 *
 * 2. Anonymous classes *cannot* extend this Enum class, as defining a child
 * anonymous class overrides the constructor, and we have defined the Enum
 * constructor to be both final and protected as a trade off for better
 * immutability and object caching.
 */
abstract class Enum implements JsonSerializable, Stringable
{
    /**
     * @var string|int|float|bool
     */
    protected $value;

    private static array $cache;

    /**
     * Return an array of unique, scalar values indexed by the name of the
     * method that will be used to construct and return the enum object instance
     *
     * @return string[]|int[]|float[]|bool[]
     */
    abstract public static function getValues(): array;

    final protected function __construct(string $key)
    {
        if (!array_key_exists($key, static::getValues())) {
            throw new UnexpectedValueException('Invalid Enum Key');
        }

        $this->value = static::getValues()[$key];
        if (!is_scalar($this->value)) {
            throw new \DomainException('Enum Values Must Be Scalar');
        }
    }

    /**
     * Return an instance of the Enumeration by key
     */
    final public static function init(string $key): self
    {
        return self::$cache[static::class][$key] ??= new static($key);
    }

    /**
     * Return an instance of the Enumeration by a scalar value. This would be
     * used to cast a value from a database into an enum object.
     *
     * @param string|int|float|bool $value
     */
    final public static function make($value): self
    {
        $key = array_search($value, static::getValues(), true);
        if ($key === false) {
            throw new InvalidArgumentException('Invalid Enum Value');
        }

        return self::init($key);
    }

    final public static function __callStatic($key, $args): self
    {
        return self::init($key);
    }

    final public function __toString(): string
    {
        return (string)$this->value;
    }

    final public function __set(string $name, $value): void
    {
        throw new ReadOnly('Enums are Immutable');
    }

    /**
     * Return the underlying scalar value.
     *
     * @return string|int|float|bool
     */
    final public function getValue()
    {
        return $this->value;
    }

    /**
     * Return the key string associated with the instance value
     *
     * @return string
     */
    final public function getKey(): string
    {
        return (string)array_flip(static::getValues())[$this->value];
    }

    /**
     * Return an array of the enum key names.
     *
     * @return string[]
     */
    final public static function getKeys(): array
    {
        return array_keys(static::getValues());
    }

    /**
     * @inheritdoc
     *
     * @return string|int|float|bool
     */
    final public function jsonSerialize()
    {
        return $this->getValue();
    }

    /**
     * Return an array of that maps the enum key to an instance of the enum object
     * for its underlying value.
     *
     * @return static[]
     */
    final public static function getEnumeration(): array
    {
        $keys = static::getKeys();
        $values = array_map(fn(string $key): self => self::init($key), $keys);

        return array_combine($keys, $values);
    }

    /**
     * Test if the passed key parameter is a valid enum key name.
     */
    final public static function isValidKey(string $key): bool
    {
        return array_key_exists($key, static::getValues());
    }

    /**
     * Test if the passed value parameter is a possible enum value. The
     * "use strict" parameter controls whether the underlying value is
     * compared by strict identity or by equality.
     *
     * @param mixed $value
     */
    final public static function isValidValue($value, bool $use_strict = true): bool
    {
        return in_array($value, static::getValues(), $use_strict);
    }

    /**
     * Strict comparison will only return true if test value is both instance
     * of the enum and holds the same underlying value.  This is the closest we
     * can get to a useful test for "identity", as we cannot guarantee that we
     * will always be working with the same object, even with caching.
     *
     * @param mixed $enum
     */
    final public function is($enum): bool
    {
        return $enum instanceof static && $enum->value === $this->value;
    }

    /**
     * Inverse of `static::is` method.
     *
     * @param mixed $enum
     */
    final public function isNot($enum): bool
    {
        return !$this->is($enum);
    }

    /**
     * Loose comparison method returns true if the thing compared is either
     * an enum of the same class and value, or if it is the underlying scalar
     * value held by the enum. The "use strict" parameter controls whether the
     * underlying value is compared by strict identity or by equality.
     *
     * @param mixed $value
     */
    final public function equals($value, bool $use_strict = true): bool
    {
        if ($this->is($value)) {
            return true;
        }

        /** @noinspection TypeUnsafeComparisonInspection */
        return $use_strict ? $this->getValue() === $value : $this->getValue() == $value;
    }

    /**
     * Inverse of `static::equals` method.
     *
     * @param mixed $value
     */
    final public function notEquals($value, bool $use_strict = true): bool
    {
        return !$this->equals($value, $use_strict);
    }

    final public static function set(self ...$enums): EnumSet
    {
        return new class(static::class, ...$enums) extends EnumSet {
            public static function set(Enum ...$enums): EnumSet
            {
                throw new LogicException('Cannot Initialize Anonymous EnumSet');
            }
        };
    }
}
