<?php

namespace PhoneBurnerTest\Enum;

use DomainException;
use Generator;
use InvalidArgumentException;
use LogicException;
use PhoneBurner\Enum\Enum;
use PhoneBurner\Enum\EnumSet;
use PhoneBurner\Enum\Exception\ReadOnly;
use PhoneBurnerTest\Enum\Fixtures\ErrorLevel;
use PhoneBurnerTest\Enum\Fixtures\Stoplight;
use PhoneBurnerTest\Enum\Fixtures\StoplightSet;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \PhoneBurner\Enum\Enum::set
 */
class EnumSetTest extends TestCase
{
    /**
     * @test
     */
    public function set_requires_enum_set(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Set Class Must Extend Enum");
        new class(stdClass::class, Stoplight::GREEN(), ErrorLevel::ERROR()) extends EnumSet {
            public static function set(Enum ...$enums): EnumSet
            {
                throw new LogicException('Cannot Initialize Anonymous EnumSet');
            }
        };
    }

    /**
     * @test
     */
    public function members_of_set_are_same_enum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("All Enums In Set Must Be Instance");
        ErrorLevel::set(...[
            ErrorLevel::ERROR(),
            ErrorLevel::COMPILE_ERROR(),
            Stoplight::RED(),
        ]);
    }

    /**
     * @test
     */
    public function all_returns_full_enum_set(): void
    {
        $set = Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN());
        $all = Stoplight::set()->all();

        self::assertTrue($set->is($all));
        self::assertTrue($all->is($set));
    }

    /**
     * @test
     */
    public function sets_are_read_only(): void
    {
        $set = ErrorLevel::set(ErrorLevel::WARNING());

        $this->expectException(ReadOnly::class);
        $set->foo = 1234;
    }

    /**
     * @test
     */
    public function sets_can_only_be_constructed_once(): void
    {
        $set = ErrorLevel::set(ErrorLevel::WARNING());

        $this->expectException(ReadOnly::class);
        $set->__construct(ErrorLevel::ERROR());
    }

    /**
     * @test
     */
    public function sets_can_only_be_constructed_once_even_if_empty(): void
    {
        $set = ErrorLevel::set();

        $this->expectException(ReadOnly::class);
        $set->__construct(ErrorLevel::ERROR());
    }

    /**
     * @test
     */
    public function sets_are_iterable(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]);

        self::assertIsIterable($set);

        $counter = 0;
        foreach ($set as $enum) {
            self::assertTrue($set->contains($enum));
            ++$counter;
        }

        self::assertCount($counter, $set);
    }

    /**
     * @test
     */
    public function getEnumClass_returns_name_of_enum_class(): void
    {
        self::assertSame(ErrorLevel::class, ErrorLevel::set()->getEnumClass());
    }

    /**
     * @test
     */
    public function getEnums_returns_an_array_of_the_enum_objects_with_keys(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]);

        self::assertEquals([
            'WARNING' => ErrorLevel::WARNING(),
            'NOTICE' => ErrorLevel::NOTICE(),
            'ERROR' => ErrorLevel::ERROR(),
        ], $set->getEnums());
    }

    /**
     * @test
     */
    public function getValues_returns_an_array_of_the_enum_objects_values(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]);

        self::assertEquals([
            'WARNING' => 1 << 1,
            'NOTICE' => 1 << 3,
            'ERROR' => 1 << 0,
        ], $set->getValues());
    }

    /**
     * @test
     */
    public function getKeys_returns_an_array_of_the_enum_objects_keys(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]);

        self::assertEquals([
            'WARNING' => 'WARNING',
            'NOTICE' => 'NOTICE',
            'ERROR' => 'ERROR',
        ], $set->getKeys());
    }

    /**
     * @test
     * @dataProvider providesEmptySets
     */
    public function isEmptySet_returns_true_if_set_has_no_members(EnumSet $set): void
    {
        self::assertTrue($set->isEmptySet());
    }

    /**
     * @test
     */
    public function isEmptySet_returns_false_if_set_has_members(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]);

        self::assertFalse($set->isEmptySet());
    }

    /**
     * @test
     * @dataProvider providesEmptySets
     */
    public function isNotEmptySet_returns_false_if_set_is_empty(EnumSet $set): void
    {
        self::assertFalse($set->isNotEmptySet());
    }

    /**
     * @test
     */
    public function isNotEmptySet_returns_true_if_set_has_members(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]);

        self::assertTrue($set->isNotEmptySet());
    }

    public function providesEmptySets(): Generator
    {
        yield [Stoplight::set()];
        yield [StoplightSet::set()];
        yield [StoplightSet::set(...[])];
    }


    /**
     * @test
     */
    public function contains_returns_true_if_subset_is_in_set(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]);

        self::assertTrue($set->contains(ErrorLevel::WARNING()));
        self::assertTrue($set->contains(ErrorLevel::NOTICE()));
        self::assertTrue($set->contains(ErrorLevel::ERROR()));
        self::assertTrue($set->contains(ErrorLevel::WARNING(), ErrorLevel::ERROR()));
        self::assertTrue($set->contains(ErrorLevel::WARNING(), ErrorLevel::ERROR(), ErrorLevel::NOTICE()));
        self::assertFalse($set->contains(ErrorLevel::DEPRECATED()));
        self::assertFalse($set->contains(ErrorLevel::WARNING(), ErrorLevel::DEPRECATED()));
        self::assertFalse($set->contains(Stoplight::GREEN()));
        self::assertFalse($set->contains(ErrorLevel::WARNING(), ErrorLevel::ERROR(), Stoplight::RED()));
    }

    /**
     * @test
     * @dataProvider providesCountableSets
     */
    public function count_returns_cardinality_of_set(int $expected, EnumSet $set): void
    {
        self::assertCount($expected, $set);
        self::assertSame($expected, $set->count());
    }

    public function providesCountableSets(): Generator
    {
        yield 'empty_set' => [0, ErrorLevel::set()];

        yield 'single_member' => [1, ErrorLevel::set(ErrorLevel::ERROR())];

        yield 'happy_path' => [3, ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]),];

        yield 'duplicates' => [3, ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::WARNING(),
            ErrorLevel::WARNING(),
            ErrorLevel::WARNING(),
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::NOTICE(),
            ErrorLevel::NOTICE(),
            ErrorLevel::NOTICE(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
            ErrorLevel::ERROR(),
            ErrorLevel::ERROR(),
            ErrorLevel::ERROR(),
            ErrorLevel::ERROR(),
            ErrorLevel::ERROR(),
            ErrorLevel::ERROR(),
        ]),];
    }

    /**
     * @test
     */
    public function add_returns_new_set_with_added_enum(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]);

        $new = $set->add(ErrorLevel::PARSE());

        self::assertNotEquals($set, $new);
        self::assertCount(4, $new);
        self::assertTrue($new->contains(...[
            ErrorLevel::PARSE(),
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]));

        self::assertCount(3, $set);
        self::assertTrue($set->contains(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]));
    }

    /**
     * @test
     */
    public function add_takes_variadic_arguments(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]);

        $new = $set->add(...[
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
            ErrorLevel::DEPRECATED(),
            ErrorLevel::PARSE(),
        ]);

        self::assertCount(5, $new);
        self::assertTrue($new->contains(...[
            ErrorLevel::PARSE(),
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::DEPRECATED(),
            ErrorLevel::ERROR(),
        ]));
    }

    /**
     * @test
     */
    public function remove_returns_new_set_without_enum(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]);

        $new = $set->remove(ErrorLevel::NOTICE());

        self::assertNotEquals($set, $new);
        self::assertCount(2, $new);
        self::assertTrue($new->contains(...[
            ErrorLevel::WARNING(),
            ErrorLevel::ERROR(),
        ]));

        self::assertCount(3, $set);
        self::assertTrue($set->contains(...[
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::ERROR(),
        ]));
    }

    /**
     * @test
     */
    public function remove_takes_variadic_arguments(): void
    {
        $set = ErrorLevel::set(...[
            ErrorLevel::PARSE(),
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::DEPRECATED(),
            ErrorLevel::ERROR(),
        ]);

        $new = $set->remove(...[
            ErrorLevel::NOTICE(),
            ErrorLevel::PARSE(),
            ErrorLevel::WARNING(),
            ErrorLevel::NOTICE(),
            ErrorLevel::DEPRECATED(),
        ]);

        self::assertNotEquals($set, $new);
        self::assertCount(1, $new);
        self::assertTrue($new->contains(...[
            ErrorLevel::ERROR(),
        ]));
    }

    /**
     * @test
     * @dataProvider providesSetsForIsComparison
     */
    public function is_returns_true_if_sets_are_the_same_sets(bool $expected, EnumSet $set1, EnumSet $set2): void
    {
        self::assertSame($expected, $set1->is($set2));
    }

    /**
     * @test
     * @dataProvider providesSetsForIsComparison
     */
    public function isNot_returns_false_if_sets_are_the_same_sets(bool $expected, EnumSet $set1, EnumSet $set2): void
    {
        self::assertSame(!$expected, $set1->isNot($set2));
    }

    public function providesSetsForIsComparison(): Generator
    {
        $set = ErrorLevel::set(ErrorLevel::ERROR(), ErrorLevel::NOTICE(), ErrorLevel::WARNING());
        yield 'same_object' => [
            true,
            $set,
            $set,
        ];

        $cloned = $set->clone();
        self::assertNotSame($set, $cloned);
        yield 'different_object_same_order' => [
            true,
            $set,
            $cloned,
        ];

        yield 'different_objects_different_order_same_enums' => [
            true,
            Stoplight::set(Stoplight::GREEN(), Stoplight::RED(), Stoplight::YELLOW()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'named_and_anonymous_classes_referencing_same_set' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            StoplightSet::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'named_and_anonymous_classes_referencing_same_set_different_values' => [
            false,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            StoplightSet::set(Stoplight::GREEN()),
        ];

        yield 'subset_enums' => [
            false,
            Stoplight::set(Stoplight::GREEN(), Stoplight::YELLOW()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'superset_enums' => [
            false,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::GREEN(), Stoplight::YELLOW()),
        ];

        yield 'different_enums' => [
            false,
            ErrorLevel::set(ErrorLevel::ERROR()),
            ErrorLevel::set(ErrorLevel::DEPRECATED()),
        ];

        yield 'different_enum_class' => [
            false,
            ErrorLevel::set(ErrorLevel::ERROR(), ErrorLevel::NOTICE()),
            Stoplight::set(Stoplight::GREEN(), Stoplight::YELLOW()),
        ];
    }

    /**
     * @test
     * @dataProvider providesSubsetTestCases
     */
    public function isSubsetOf_returns_true_for_subsets(bool $expected, EnumSet $set1, EnumSet $set2): void
    {
        self::assertSame($expected, $set1->isSubsetOf($set2));
    }

    public function providesSubsetTestCases(): Generator
    {
        yield 'empty_set_is_subset_of_same_enum_set' => [
            true,
            StoplightSet::set(),
            StoplightSet::set(Stoplight::GREEN(), Stoplight::RED()),
        ];

        yield 'empty_set_is_subset_of_different_enum_set' => [
            true,
            ErrorLevel::set(),
            StoplightSet::set(Stoplight::GREEN(), Stoplight::RED()),
        ];

        yield 'set_is_subset_of_self' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'subset_is_subset_of_set' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'superset_is_not_subset_of_set' => [
            false,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::GREEN()),
        ];
    }

    /**
     * @test
     * @dataProvider providesSupersetTestCases
     */
    public function isSupersetOf_returns_true_for_supersets(bool $expected, EnumSet $set1, EnumSet $set2): void
    {
        self::assertSame($expected, $set1->isSupersetOf($set2));
    }

    public function providesSupersetTestCases(): Generator
    {
        yield 'empty_set_is_not_superset_of_same_enum_set' => [
            false,
            StoplightSet::set(),
            StoplightSet::set(Stoplight::GREEN(), Stoplight::RED()),
        ];

        yield 'empty_set_is_not_superset_of_different_enum_set' => [
            false,
            ErrorLevel::set(),
            StoplightSet::set(Stoplight::GREEN(), Stoplight::RED()),
        ];

        yield 'set_is_superset_of_self' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'subset_is_not_superset_of_set' => [
            false,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'superset_is_superset_of_set' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::GREEN()),
        ];

        yield 'superset_is_superset_of_empty_set' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(),
        ];

        yield 'superset_is_superset_of_different_enum_empty_set' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            ErrorLevel::set(),
        ];
    }

    /**
     * @test
     * @dataProvider providesStrictSubsetTestCases
     */
    public function isStrictSubsetOf_returns_true_for_subsets(bool $expected, EnumSet $set1, EnumSet $set2): void
    {
        self::assertSame($expected, $set1->isStrictSubsetOf($set2));
    }

    public function providesStrictSubsetTestCases(): Generator
    {
        yield 'empty_set_is_not_strict_subset_of_empty_set' => [
            false,
            StoplightSet::set(),
            StoplightSet::set(),
        ];

        yield 'empty_set_is_strict_subset_of_same_enum_set' => [
            true,
            StoplightSet::set(),
            StoplightSet::set(Stoplight::GREEN(), Stoplight::RED()),
        ];

        yield 'empty_set_is_strict_subset_of_different_enum_set' => [
            true,
            ErrorLevel::set(),
            StoplightSet::set(Stoplight::GREEN(), Stoplight::RED()),
        ];

        yield 'set_is_not_strict_subset_of_self' => [
            false,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'strict_subset_is_strict_subset_of_set' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'superset_is_not_strict_subset_of_set' => [
            false,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::GREEN()),
        ];
    }

    /**
     * @test
     * @dataProvider providesStrictSupersetTestCases
     */
    public function isStrictSupersetOf_returns_true_for_strict_supersets(bool $expected, EnumSet $set1, EnumSet $set2): void
    {
        self::assertSame($expected, $set1->isStrictSupersetOf($set2));
    }

    public function providesStrictSupersetTestCases(): Generator
    {
        yield 'empty_set_is_not_strict_superset_of_same_enum_set' => [
            false,
            StoplightSet::set(),
            StoplightSet::set(Stoplight::GREEN(), Stoplight::RED()),
        ];

        yield 'empty_set_is_not_strict_superset_of_different_enum_set' => [
            false,
            ErrorLevel::set(),
            StoplightSet::set(Stoplight::GREEN(), Stoplight::RED()),
        ];

        yield 'set_is_not_strict_superset_of_self' => [
            false,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'subset_is_not_strict_superset_of_set' => [
            false,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
        ];

        yield 'strict_superset_is_strict_superset_of_set' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(Stoplight::YELLOW(), Stoplight::GREEN()),
        ];

        yield 'strict_superset_is_strict_superset_of_empty_set' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            Stoplight::set(),
        ];

        yield 'strict_superset_is_strict_superset_of_different_enum_empty_set' => [
            true,
            Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN()),
            ErrorLevel::set(),
        ];
    }

    /**
     * @test
     */
    public function clone_returns_new_instance_with_same_set(): void
    {
        $set = Stoplight::set(Stoplight::YELLOW(), Stoplight::RED(), Stoplight::GREEN());

        $clone = $set->clone();

        self::assertNotSame($set, $clone);
        self::assertTrue($set->is($clone));
        self::assertTrue($clone->is($set));
    }

    /**
     * @test
     * @dataProvider providesSetOperationTestCases
     */
    public function intersect_returns_set_intersection(array $test_case): void
    {
        $intersection = $test_case['set1']->intersect($test_case['set2']);

        self::assertTrue($test_case['intersection']->is($intersection));
    }

    /**
     * @test
     * @dataProvider providesSetOperationTestCases
     */
    public function union_returns_set_union(array $test_case): void
    {
        $union = $test_case['set1']->union($test_case['set2']);

        self::assertTrue($test_case['union']->is($union));
    }

    /**
     * @test
     * @dataProvider providesSetOperationTestCases
     */
    public function diff_returns_set_difference(array $test_case): void
    {
        $difference = $test_case['set1']->diff($test_case['set2']);

        self::assertTrue($test_case['difference']->is($difference));
    }

    public function providesSetOperationTestCases(): Generator
    {
        yield [[
            'set1' => Stoplight::set(),
            'set2' => Stoplight::set(),
            'intersection' => StoplightSet::set(),
            'union' => StoplightSet::set(),
            'difference' => StoplightSet::set(),
        ],];

        yield [[
            'set1' => Stoplight::set(),
            'set2' => ErrorLevel::set(),
            'intersection' => StoplightSet::set(),
            'union' => StoplightSet::set(),
            'difference' => StoplightSet::set(),
        ],];

        yield [[
            'set1' => Stoplight::set(),
            'set2' => ErrorLevel::set(ErrorLevel::ERROR()),
            'intersection' => StoplightSet::set(),
            'union' => StoplightSet::set(),
            'difference' => StoplightSet::set(),
        ],];

        yield [[
            'set1' => Stoplight::set()->all(),
            'set2' => Stoplight::set(Stoplight::RED(), Stoplight::GREEN()),
            'intersection' => StoplightSet::set(Stoplight::RED(), Stoplight::GREEN()),
            'union' => StoplightSet::set()->all(),
            'difference' => StoplightSet::set(Stoplight::YELLOW()),
        ],];

        yield [[
            'set1' => Stoplight::set()->all(),
            'set2' => ErrorLevel::set()->all(),
            'intersection' => StoplightSet::set(),
            'union' => StoplightSet::set()->all(),
            'difference' => StoplightSet::set()->all(),
        ],];

        yield [[
            'set1' => ErrorLevel::set(...[
                ErrorLevel::ERROR(),
                ErrorLevel::WARNING(),
                ErrorLevel::PARSE(),
                ErrorLevel::DEPRECATED(),
            ]),
            'set2' => ErrorLevel::set(...[
                ErrorLevel::DEPRECATED(),
                ErrorLevel::PARSE(),
                ErrorLevel::NOTICE(),
                ErrorLevel::COMPILE_ERROR(),
                ErrorLevel::COMPILE_WARNING(),
            ]),
            'intersection' => ErrorLevel::set(...[
                ErrorLevel::PARSE(),
                ErrorLevel::DEPRECATED(),
            ]),
            'union' => ErrorLevel::set(...[
                ErrorLevel::ERROR(),
                ErrorLevel::WARNING(),
                ErrorLevel::PARSE(),
                ErrorLevel::NOTICE(),
                ErrorLevel::DEPRECATED(),
                ErrorLevel::COMPILE_ERROR(),
                ErrorLevel::COMPILE_WARNING(),
            ]),
            'difference' => ErrorLevel::set(...[
                ErrorLevel::WARNING(),
                ErrorLevel::ERROR(),
            ]),
        ],];
    }
}
