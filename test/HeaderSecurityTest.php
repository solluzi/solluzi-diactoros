<?php

declare(strict_types=1);

namespace LaminasTest\Diactoros;

use InvalidArgumentException;
use Laminas\Diactoros\HeaderSecurity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

final class HeaderSecurityTest extends TestCase
{
    /**
     * Data for filter value
     *
     * @return non-empty-list<array{non-empty-string, non-empty-string}>
     */
    public static function getFilterValues(): array
    {
        return [
            ["This is a\n test", "This is a test"],
            ["This is a\r test", "This is a test"],
            ["This is a\n\r test", "This is a test"],
            ["This is a\r\n  test", "This is a\r\n  test"],
            ["This is a \r\ntest", "This is a test"],
            ["This is a \r\n\n test", "This is a  test"],
            ["This is a\n\n test", "This is a test"],
            ["This is a\r\r test", "This is a test"],
            ["This is a \r\r\n test", "This is a \r\n test"],
            ["This is a \r\n\r\ntest", "This is a test"],
            ["This is a \r\n\n\r\n test", "This is a \r\n test"],
            ["This is a test\n", "This is a test"],
        ];
    }

    /**
     * @param non-empty-string $value
     * @param non-empty-string $expected
     */
    #[DataProvider('getFilterValues')]
    #[Group('ZF2015-04')]
    public function testFiltersValuesPerRfc7230(string $value, string $expected): void
    {
        $this->assertSame($expected, HeaderSecurity::filter($value));
    }

    /** @return non-empty-list<array{non-empty-string, bool}> */
    public static function validateValues(): array
    {
        return [
            ["This is a\n test", false],
            ["This is a\r test", false],
            ["This is a\n\r test", false],
            ["This is a\r\n  test", true],
            ["This is a \r\ntest", false],
            ["This is a \r\n\n test", false],
            ["This is a\n\n test", false],
            ["This is a\r\r test", false],
            ["This is a \r\r\n test", false],
            ["This is a \r\n\r\ntest", false],
            ["This is a \r\n\n\r\n test", false],
            ["This is a \xFF test", false],
            ["This is a \x7F test", false],
            ["This is a \x7E test", true],
            ["This is a test\n", false],
        ];
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('validateValues')]
    #[Group('ZF2015-04')]
    public function testValidatesValuesPerRfc7230(string $value, bool $expected): void
    {
        self::assertSame($expected, HeaderSecurity::isValid($value));
    }

    /** @return non-empty-list<array{non-empty-string}> */
    public static function assertValues(): array
    {
        return [
            ["This is a\n test"],
            ["This is a\r test"],
            ["This is a\n\r test"],
            ["This is a \r\ntest"],
            ["This is a \r\n\n test"],
            ["This is a\n\n test"],
            ["This is a\r\r test"],
            ["This is a \r\r\n test"],
            ["This is a \r\n\r\ntest"],
            ["This is a \r\n\n\r\n test"],
            ["This is a test\n"],
        ];
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('assertValues')]
    #[Group('ZF2015-04')]
    public function testAssertValidRaisesExceptionForInvalidValue(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        HeaderSecurity::assertValid($value);
    }

    /** @return non-empty-list<array{non-empty-string}> */
    public static function assertNames(): array
    {
        return [
            ["test\n"],
            ["\ntest"],
            ["foo\r\n bar"],
            ["f\x00o"],
            ["foo bar"],
            [":foo"],
            ["foo:"],
        ];
    }

    /**
     * @param non-empty-string $value
     */
    #[DataProvider('assertNames')]
    public function testAssertValidNameRaisesExceptionForInvalidName(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        HeaderSecurity::assertValidName($value);
    }
}
