<?php

namespace PhoneBurner\Enum;

use Countable;
use DomainException;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use PhoneBurner\Enum\Exception\ReadOnly;

abstract class EnumSet implements Countable, IteratorAggregate
{
    protected string $enum_class;

    /**
     * @var Enum[]
     */
    protected array $enum_set;

    /**
     * @param array|Enum[] $enums
     */
    final public function __construct(string $enum_class, Enum ...$enums)
    {
        if (isset($this->enum_set)) {
            throw new ReadOnly('Enum Sets are Immutable');
        }

        if (!is_a($enum_class, Enum::class, true)) {
            throw new DomainException('Set Class Must Extend Enum');
        }
        $this->enum_class = $enum_class;

        $this->enum_set = [];
        foreach ($enums as $enum) {
            if (!is_a($enum, $this->enum_class, false)) {
                $message = 'All Enums In Set Must Be Instance of ' . $this->enum_class;
                throw new InvalidArgumentException($message);
            }

            $this->enum_set[$enum->getKey()] = $enum;
        }
    }

    /**
     * This method is intended to be used by "named enum sets" that extend this
     * class and need a way to initialize the set, since the constructor is
     * final.  This mirrors the `set` method on the Enum base class.
     */
    abstract public static function set(Enum ...$enums): EnumSet;

    final protected function make(Enum ...$enums): EnumSet
    {
        return new static($this->enum_class, ...$enums);
    }

    final public function all(): self
    {
        return $this->make(...array_values($this->enum_class::getEnumeration()));
    }

    final public function __set(string $name, $value): void
    {
        throw new ReadOnly('Enum Sets are Immutable');
    }

    final public function getEnumClass(): string
    {
        return $this->enum_class;
    }

    /**
     * @return Enum[]
     */
    final public function getEnums(): array
    {
        return $this->enum_set;
    }

    /**
     * @return string[]|int[]|float[]|bool[]
     */
    final public function getValues(): array
    {
        return array_map(fn(Enum $enum) => $enum->getValue(), $this->enum_set);
    }

    /**
     * @return string[]
     */
    final public function getKeys(): array
    {
        $keys = array_keys($this->enum_set);
        return array_combine($keys, $keys);
    }

    /**
     * Return a new immutable set with the passed enum object
     *
     * @return static
     */
    final public function add(Enum ...$enums): self
    {
        $new_set = $this->enum_set;

        foreach ($enums as $enum) {
            $new_set[$enum->getKey()] = $enum;
        }

        return $this->make(...array_values($new_set));
    }

    /**
     * Return a new immutable set without the passed enum objects.
     *
     * @return static
     */
    final public function remove(Enum ...$enums): self
    {
        $new_set = $this->enum_set;

        foreach ($enums as $enum) {
            unset($new_set[$enum->getKey()]);
        }

        return $this->make(...array_values($new_set));
    }

    /**
     * Returns true if the set contains all the enum(s) passed as arguments
     */
    final public function contains(Enum ...$enums): bool
    {
        foreach ($enums as $enum) {
            if (!is_a($enum, $this->enum_class, false)) {
                return false;
            }
            if (!array_key_exists($enum->getKey(), $this->enum_set)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if both sets hold the same kind of enum instances and if
     * they hold instances of the same enum objects
     */
    final public function is(EnumSet $set): bool
    {
        if ($this->getEnumClass() !== $set->getEnumClass()) {
            return false;
        }

        if ($this->count() !== $set->count()) {
            return false;
        }

        foreach ($set as $enum) {
            if (!array_key_exists($enum->getKey(), $this->enum_set)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Inverse of `static::is` method.
     */
    final public function isNot(EnumSet $set): bool
    {
        return !$this->is($set);
    }

    /**
     * Returns true if *all* enum objects in `$this` set are also in `$set'
     */
    final public function isSubsetOf(EnumSet $set): bool
    {
        return $this->intersect($set)->is($this);
    }

    /**
     * Returns true if *all* enum objects in `$this` set are also in `$set',
     * and `$set` has at least one set member not in `$this`
     */
    final public function isStrictSubsetOf(EnumSet $set): bool
    {
        return $this->count() < $set->count() && $this->intersect($set)->is($this);
    }

    /**
     * Returns true if *all* enum objects in `$set` are also in `$this' set
     */
    final public function isSupersetOf(EnumSet $set): bool
    {
        return $this->isNotEmptySet() && $this->union($set)->is($this);
    }

    /**
     * Returns true if *all* enum objects in `$set` are also in `$this' set
     * and `$this` has at least one more member than `$set`
     */
    final public function isStrictSupersetOf(EnumSet $set): bool
    {
        return $this->count() > $set->count() && $this->union($set)->is($this);
    }

    /**
     * Returns true if this set is the empty set instance for the Enum Class.
     */
    final public function isEmptySet(): bool
    {
        return $this->enum_set === [];
    }

    /**
     * Returns true if this set is the empty set instance for the Enum Class.
     */
    final public function isNotEmptySet(): bool
    {
        return !$this->isEmptySet();
    }

    /**
     * Returns the cardinality of `$this` set
     */
    final public function count(): int
    {
        return count($this->enum_set);
    }

    /**
     * Returns a new instance of `$this` set with the same values
     */
    final public function clone(): self
    {
        return clone $this;
    }

    /**
     * Returns a set of enum objects that are in `$this` set and `$set`
     */
    final public function intersect(EnumSet $set): EnumSet
    {
        $intersection = $this->make();
        foreach ($this as $enum) {
            if ($set->contains($enum)) {
                $intersection = $intersection->add($enum);
            }
        }

        return $intersection;
    }

    /**
     * Returns a set of enum objects that are in `$this` set or `$set`
     */
    final public function union(EnumSet $set): EnumSet
    {
        $union = $this->clone();
        foreach ($set as $enum) {
            if (is_a($enum, $this->enum_class, false)) {
                $union = $union->add($enum);
            }
        }

        return $union;
    }

    /**
     * Returns a set of enum objects that are in `$this` set but not `$set`
     */
    final public function diff(EnumSet $set): EnumSet
    {
        $difference = $this->make();
        foreach ($this as $enum) {
            if (!$set->contains($enum)) {
                $difference = $difference->add($enum);
            }
        }

        return $difference;
    }

    /**
     * @return Generator|Enum[]
     */
    final public function getIterator(): Generator
    {
        yield from $this->enum_set;
    }
}
