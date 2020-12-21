<?php

namespace PhoneBurnerTest\Enum;

use ReflectionClass;

trait EnumImplementationTestCases
{
    abstract protected function getEnumClassString(): string;

    /**
     * @test
     */
    public function enum_implementation_getValues_provides_nonempty_array_of_values(): void
    {
        $values = $this->getEnumClassString()::getValues();

        self::assertIsArray($values);
        self::assertNotEmpty($values);
    }

    /**
     * @test
     */
    public function enum_implementation_keys_are_uppercase_strings(): void
    {
        $keys = $this->getEnumClassString()::getKeys();

        self::assertContainsOnly('string', $keys);
        foreach ($keys as $key) {
            self::assertRegExp('/^[A-Z].[A-Z0-9_-]*$/', $key);
        }
    }

    /**
     * @test
     */
    public function enum_implementation_values_are_same_scalar_type(): void
    {
        $values = $this->getEnumClassString()::getValues();

        self::assertContainsOnly('scalar', $values);
        self::assertContainsOnly(getType(reset($values)), $values);
    }

    /**
     * @test
     */
    public function enum_implementation_values_are_unique(): void
    {
        $values = $this->getEnumClassString()::getValues();
        $hashed = array_map(fn($x) => sodium_crypto_generichash($x), $values);

        self::assertSameSize($values, array_flip($hashed));
    }

    /**
     * @test
     */
    public function enum_implementation_is_marked_final(): void
    {
        self::assertTrue((new ReflectionClass($this->getEnumClassString()))->isFinal());
    }
}
