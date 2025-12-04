<?php

declare(strict_types=1);

namespace Janklan\SimpleDateTime\Tests;

use PHPUnit\Framework\TestCase;
use Janklan\SimpleDateTime\Date;
use Janklan\SimpleDateTime\DateImmutable;

class DateTest extends TestCase
{
    // ========================================================================
    // Construction Tests
    // ========================================================================

    public function testConstructWithDateString(): void
    {
        $date = new Date('2025-01-15');

        $this->assertSame('2025-01-15', $date->format('Y-m-d'));
    }

    public function testConstructWithDateTimeString(): void
    {
        // Time component should be ignored
        $date = new Date('2025-01-15 14:30:45');

        $this->assertSame('2025-01-15', $date->format('Y-m-d'));
    }

    public function testConstructNormalizesToMidnightUtc(): void
    {
        $date = new Date('2025-01-15 14:30:45');

        // Internal time should be midnight UTC
        $this->assertSame('00:00:00', self::formatInternal($date, 'H:i:s'));
        // Timezone can be 'UTC' or '+00:00' depending on PHP version
        $this->assertSame(0, $date->getTimezone()->getOffset($date));
    }

    public function testTodayFactory(): void
    {
        $today = Date::today();
        $expected = (new \DateTime('today'))->format('Y-m-d');

        $this->assertSame($expected, $today->format('Y-m-d'));
    }

    public function testFromStringFactory(): void
    {
        $date = Date::fromString('2025-06-20');

        $this->assertSame('2025-06-20', $date->format('Y-m-d'));
    }

    public function testFromStringFactoryThrowsOnInvalidDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse "not-a-date" as a date');

        Date::fromString('not-a-date');
    }

    public function testFromDateTimeFactory(): void
    {
        $dateTime = new \DateTime('2025-03-10 16:45:30');
        $date = Date::fromDateTime($dateTime);

        $this->assertSame('2025-03-10', $date->format('Y-m-d'));
    }

    public function testCreateFromFormat(): void
    {
        $date = Date::createFromFormat('d/m/Y', '25/12/2025');

        $this->assertSame('2025-12-25', $date->format('Y-m-d'));
    }

    public function testCreateFromFormatThrowsOnInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Date::createFromFormat('Y-m-d', 'invalid');
    }

    // ========================================================================
    // Blocked Operations Tests
    // ========================================================================

    public function testSetTimeThrowsLogicException(): void
    {
        $date = new Date('2025-01-15');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setTime() is not supported on Date objects');

        $date->setTime(14, 30);
    }

    public function testSetTimezoneThrowsLogicException(): void
    {
        $date = new Date('2025-01-15');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setTimezone() is not supported on Date objects');

        $date->setTimezone(new \DateTimeZone('America/New_York'));
    }

    // ========================================================================
    // Format Validation Tests
    // ========================================================================

    public function testFormatAllowsDateCharacters(): void
    {
        $date = new Date('2025-01-15');

        $this->assertSame('2025', $date->format('Y'));
        $this->assertSame('01', $date->format('m'));
        $this->assertSame('15', $date->format('d'));
        $this->assertSame('January', $date->format('F'));
        $this->assertSame('Wednesday', $date->format('l'));
    }

    public function testFormatBlocksTimeCharacters(): void
    {
        $date = new Date('2025-01-15');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Format character "H" is not allowed');

        $date->format('H:i:s');
    }

    public function testFormatBlocksTimezoneCharacters(): void
    {
        $date = new Date('2025-01-15');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Format character "e" is not allowed');

        $date->format('e');
    }

    public function testFormatAllowsEscapedCharacters(): void
    {
        $date = new Date('2025-01-15');

        $this->assertSame('2025-01-15 H:i:s', $date->format('Y-m-d \H:\i:\s'));
    }

    // ========================================================================
    // Modify Validation Tests
    // ========================================================================

    public function testModifyAllowsDateModifiers(): void
    {
        $date = new Date('2025-01-15');

        $date->modify('+1 day');
        $this->assertSame('2025-01-16', $date->format('Y-m-d'));
    }

    public function testModifyBlocksTimeModifiers(): void
    {
        $date = new Date('2025-01-15');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('affects time components');

        $date->modify('+1 hour');
    }

    public function testMutableModifyReturnsSelf(): void
    {
        $date = new Date('2025-01-15');
        $result = $date->modify('+1 day');

        $this->assertSame($date, $result);
        $this->assertSame('2025-01-16', $date->format('Y-m-d'));
    }

    public function testModifyNormalizesResult(): void
    {
        $date = new Date('2025-01-15');
        $date->modify('+1 day');

        $this->assertSame('00:00:00', self::formatInternal($date, 'H:i:s'));
        $this->assertSame('UTC', $date->getTimezone()->getName());
    }

    // ========================================================================
    // Arithmetic Tests
    // ========================================================================

    public function testAddDateInterval(): void
    {
        $date = new Date('2025-01-15');
        $result = $date->add(new \DateInterval('P1D'));

        $this->assertSame($date, $result); // Mutable - returns self
        $this->assertSame('2025-01-16', $date->format('Y-m-d'));
    }

    public function testSubDateInterval(): void
    {
        $date = new Date('2025-01-15');
        $result = $date->sub(new \DateInterval('P1D'));

        $this->assertSame($date, $result);
        $this->assertSame('2025-01-14', $date->format('Y-m-d'));
    }

    public function testAddNormalizesResult(): void
    {
        $date = new Date('2025-01-15');
        $date->add(new \DateInterval('P1DT5H'));

        // Time should be normalized to midnight
        $this->assertSame('00:00:00', self::formatInternal($date, 'H:i:s'));
        $this->assertSame('UTC', $date->getTimezone()->getName());
    }

    public function testSetDateNormalizesTime(): void
    {
        $date = new Date('2025-01-15');
        $result = $date->setDate(2030, 6, 20);

        $this->assertSame($date, $result);
        $this->assertSame('2030-06-20', $date->format('Y-m-d'));
        $this->assertSame('00:00:00', self::formatInternal($date, 'H:i:s'));
    }

    public function testSetISODateNormalizesTime(): void
    {
        $date = new Date('2025-01-15');
        $result = $date->setISODate(2025, 10, 3); // Week 10, Wednesday

        $this->assertSame($date, $result);
        $this->assertSame('00:00:00', self::formatInternal($date, 'H:i:s'));
    }

    public function testSetTimestampNormalizesTime(): void
    {
        $date = new Date('2025-01-15');
        $timestamp = (new \DateTime('2025-06-15 14:30:00 UTC'))->getTimestamp();
        $result = $date->setTimestamp($timestamp);

        $this->assertSame($date, $result);
        $this->assertSame('2025-06-15', $date->format('Y-m-d'));
        $this->assertSame('00:00:00', self::formatInternal($date, 'H:i:s'));
    }

    // ========================================================================
    // Comparison Tests
    // ========================================================================

    public function testNativeComparisonOperators(): void
    {
        $date1 = new Date('2025-01-15');
        $date2 = new Date('2025-01-20');
        $date3 = new Date('2025-01-15');

        $this->assertTrue($date1 < $date2);
        $this->assertTrue($date2 > $date1);
        $this->assertTrue($date1 == $date3);
        $this->assertTrue($date1 != $date2);
    }

    public function testIsSameDateAs(): void
    {
        $date = new Date('2025-01-15');

        $this->assertTrue($date->isSameDateAs(new Date('2025-01-15')));
        $this->assertFalse($date->isSameDateAs(new Date('2025-01-16')));
    }

    public function testIsSameDateAsWithDateTimeImmutable(): void
    {
        $date = new Date('2025-01-15');
        $dateTimeImmutable = new \DateTimeImmutable('2025-01-15 14:30:45');

        $this->assertTrue($date->isSameDateAs($dateTimeImmutable));
    }

    public function testIsBefore(): void
    {
        $date = new Date('2025-01-15');

        $this->assertTrue($date->isBefore(new Date('2025-01-16')));
        $this->assertFalse($date->isBefore(new Date('2025-01-15')));
        $this->assertFalse($date->isBefore(new Date('2025-01-14')));
    }

    public function testIsAfter(): void
    {
        $date = new Date('2025-01-15');

        $this->assertTrue($date->isAfter(new Date('2025-01-14')));
        $this->assertFalse($date->isAfter(new Date('2025-01-15')));
        $this->assertFalse($date->isAfter(new Date('2025-01-16')));
    }

    // ========================================================================
    // Serialization Tests
    // ========================================================================

    public function testSerializeAndUnserialize(): void
    {
        $date = new Date('2025-07-04');
        $serialized = serialize($date);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(Date::class, $unserialized);
        $this->assertSame('2025-07-04', $unserialized->format('Y-m-d'));
        $this->assertSame('00:00:00', self::formatInternal($unserialized, 'H:i:s'));
    }

    public function testJsonSerialize(): void
    {
        $date = new Date('2025-12-25');

        $this->assertSame('"2025-12-25"', json_encode($date));
    }

    public function testToString(): void
    {
        $date = new Date('2025-11-11');

        $this->assertSame('2025-11-11', (string) $date);
    }

    // ========================================================================
    // Conversion Tests
    // ========================================================================

    public function testToImmutable(): void
    {
        $mutable = new Date('2025-01-15');
        $immutable = $mutable->toImmutable();

        $this->assertInstanceOf(DateImmutable::class, $immutable);
        $this->assertSame('2025-01-15', $immutable->format('Y-m-d'));
    }

    public function testToImmutableDoesNotAffectOriginal(): void
    {
        $mutable = new Date('2025-01-15');
        $immutable = $mutable->toImmutable();

        $mutable->modify('+1 day');

        $this->assertSame('2025-01-16', $mutable->format('Y-m-d'));
        $this->assertSame('2025-01-15', $immutable->format('Y-m-d'));
    }

    // ========================================================================
    // Edge Cases
    // ========================================================================

    public function testGetMicrosecondAlwaysReturnsZero(): void
    {
        $date = new Date('2025-01-15');

        $this->assertSame(0, $date->getMicrosecond());
    }

    public function testLeapYear(): void
    {
        $date = new Date('2024-02-29');
        $this->assertSame('2024-02-29', $date->format('Y-m-d'));
    }

    public function testYearBoundary(): void
    {
        $date = new Date('2025-12-31');
        $date->modify('+1 day');

        $this->assertSame('2026-01-01', $date->format('Y-m-d'));
    }

    public function testDiff(): void
    {
        $date1 = new Date('2025-01-15');
        $date2 = new Date('2025-01-20');

        $diff = $date1->diff($date2);

        $this->assertSame(5, $diff->days);
    }

    // Helper method to access parent format without validation
    private static function formatInternal(Date $date, string $format): string
    {
        return (new \ReflectionClass(\DateTime::class))
            ->getMethod('format')
            ->invoke($date, $format);
    }
}
