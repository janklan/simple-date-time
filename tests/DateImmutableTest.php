<?php

declare(strict_types=1);

namespace JanKlan\SimpleDateTime\Tests;

use JanKlan\SimpleDateTime\Date;
use JanKlan\SimpleDateTime\DateImmutable;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
class DateImmutableTest extends TestCase
{
    // ========================================================================
    // Construction Tests
    // ========================================================================

    public function testConstructWithDateString(): void
    {
        $date = new DateImmutable('2025-01-15');

        $this->assertSame('2025-01-15', $date->format('Y-m-d'));
    }

    public function testConstructWithDateTimeString(): void
    {
        // Time component should be ignored
        $date = new DateImmutable('2025-01-15 14:30:45');

        $this->assertSame('2025-01-15', $date->format('Y-m-d'));
    }

    public function testConstructWithRelativeString(): void
    {
        $date = new DateImmutable('2025-01-15');
        $nextDay = new DateImmutable('2025-01-15 +1 day');

        $this->assertSame('2025-01-16', $nextDay->format('Y-m-d'));
    }

    public function testConstructNormalizesToMidnightUtc(): void
    {
        $date = new DateImmutable('2025-01-15 14:30:45');

        // Internal time should be midnight UTC
        $this->assertSame('00:00:00', self::formatInternal($date, 'H:i:s'));
        // Timezone can be 'UTC' or '+00:00' depending on PHP version
        $this->assertSame(0, $date->getTimezone()->getOffset($date));
    }

    public function testTodayFactory(): void
    {
        $today = DateImmutable::today();
        $expected = (new \DateTimeImmutable('today'))->format('Y-m-d');

        $this->assertSame($expected, $today->format('Y-m-d'));
    }

    public function testTodayFactoryRespectsTimezone(): void
    {
        // Test with a timezone where "today" might be different from UTC
        $aucklandTz = new \DateTimeZone('Pacific/Auckland');
        $todayInAuckland = DateImmutable::today($aucklandTz);

        // The date should match what "today" is in Auckland
        $expectedDate = (new \DateTimeImmutable('now', $aucklandTz))->format('Y-m-d');
        $this->assertSame($expectedDate, $todayInAuckland->format('Y-m-d'));
    }

    public function testFromStringFactory(): void
    {
        $date = DateImmutable::fromString('2025-06-20');

        $this->assertSame('2025-06-20', $date->format('Y-m-d'));
    }

    public function testFromStringFactoryThrowsOnInvalidDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse "not-a-date" as a date');

        DateImmutable::fromString('not-a-date');
    }

    public function testFromDateTimeFactory(): void
    {
        $dateTime = new \DateTime('2025-03-10 16:45:30', new \DateTimeZone('America/New_York'));
        $date = DateImmutable::fromDateTime($dateTime);

        $this->assertSame('2025-03-10', $date->format('Y-m-d'));
    }

    public function testFromDateTimeImmutableFactory(): void
    {
        $dateTime = new \DateTimeImmutable('2025-08-25 08:15:00');
        $date = DateImmutable::fromDateTime($dateTime);

        $this->assertSame('2025-08-25', $date->format('Y-m-d'));
    }

    public function testCreateFromFormat(): void
    {
        $date = DateImmutable::createFromFormat('d/m/Y', '25/12/2025');

        $this->assertSame('2025-12-25', $date->format('Y-m-d'));
    }

    public function testCreateFromFormatThrowsOnInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse "invalid" using format "Y-m-d"');

        DateImmutable::createFromFormat('Y-m-d', 'invalid');
    }

    // ========================================================================
    // Blocked Operations Tests
    // ========================================================================

    public function testSetTimeThrowsLogicException(): void
    {
        $date = new DateImmutable('2025-01-15');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setTime() is not supported on Date objects');

        $date->setTime(14, 30);
    }

    public function testSetTimezoneThrowsLogicException(): void
    {
        $date = new DateImmutable('2025-01-15');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setTimezone() is not supported on Date objects');

        $date->setTimezone(new \DateTimeZone('America/New_York'));
    }

    // ========================================================================
    // Format Validation Tests
    // ========================================================================

    public function testFormatAllowsDateCharacters(): void
    {
        $date = new DateImmutable('2025-01-15');

        // All these should work without exception
        $this->assertSame('2025', $date->format('Y'));
        $this->assertSame('25', $date->format('y'));
        $this->assertSame('01', $date->format('m'));
        $this->assertSame('1', $date->format('n'));
        $this->assertSame('January', $date->format('F'));
        $this->assertSame('Jan', $date->format('M'));
        $this->assertSame('15', $date->format('d'));
        $this->assertSame('15', $date->format('j'));
        $this->assertSame('Wed', $date->format('D'));
        $this->assertSame('Wednesday', $date->format('l'));
        $this->assertSame('3', $date->format('N')); // Wednesday = 3
        $this->assertSame('th', $date->format('S')); // 15th
        $this->assertSame('3', $date->format('w')); // Wednesday = 3
        $this->assertSame('14', $date->format('z')); // Day of year (0-indexed)
        $this->assertSame('03', $date->format('W')); // Week number
        $this->assertSame('2025', $date->format('o')); // ISO week-numbering year
        $this->assertSame('0', $date->format('L')); // Not a leap year
        $this->assertSame('31', $date->format('t')); // Days in January
    }

    public function testFormatBlocksTimeCharacters(): void
    {
        $date = new DateImmutable('2025-01-15');
        $timeChars = ['H', 'h', 'G', 'g', 'i', 's', 'u', 'v', 'a', 'A', 'B'];

        foreach ($timeChars as $char) {
            try {
                $date->format($char);
                $this->fail(\sprintf("Expected InvalidArgumentException for format character '%s'", $char));
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    \sprintf('Format character "%s" is not allowed', $char),
                    $e->getMessage()
                );
            }
        }
    }

    public function testFormatBlocksTimezoneCharacters(): void
    {
        $date = new DateImmutable('2025-01-15');
        $tzChars = ['e', 'I', 'O', 'P', 'p', 'T', 'Z'];

        foreach ($tzChars as $char) {
            try {
                $date->format($char);
                $this->fail(\sprintf("Expected InvalidArgumentException for format character '%s'", $char));
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    \sprintf('Format character "%s" is not allowed', $char),
                    $e->getMessage()
                );
            }
        }
    }

    public function testFormatBlocksCompositeCharacters(): void
    {
        $date = new DateImmutable('2025-01-15');
        $compositeChars = ['c', 'r', 'U'];

        foreach ($compositeChars as $char) {
            try {
                $date->format($char);
                $this->fail(\sprintf("Expected InvalidArgumentException for format character '%s'", $char));
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    \sprintf('Format character "%s" is not allowed', $char),
                    $e->getMessage()
                );
            }
        }
    }

    public function testFormatAllowsEscapedCharacters(): void
    {
        $date = new DateImmutable('2025-01-15');

        // Escaped characters should be allowed (they become literal text)
        $this->assertSame('2025-01-15 H:i:s', $date->format('Y-m-d \H:\i:\s'));
        $this->assertSame('Date: 2025-01-15', $date->format('\D\a\t\e: Y-m-d'));
    }

    // ========================================================================
    // Modify Validation Tests
    // ========================================================================

    public function testModifyAllowsDateModifiers(): void
    {
        $date = new DateImmutable('2025-01-15');

        $this->assertSame('2025-01-16', $date->modify('+1 day')->format('Y-m-d'));
        $this->assertSame('2025-01-14', $date->modify('-1 day')->format('Y-m-d'));
        $this->assertSame('2025-02-15', $date->modify('+1 month')->format('Y-m-d'));
        $this->assertSame('2026-01-15', $date->modify('+1 year')->format('Y-m-d'));
        $this->assertSame('2025-01-20', $date->modify('next monday')->format('Y-m-d'));
        $this->assertSame('2025-01-31', $date->modify('last day of this month')->format('Y-m-d'));
    }

    public function testModifyBlocksTimeModifiers(): void
    {
        $date = new DateImmutable('2025-01-15');
        $timeModifiers = ['+1 hour', '-30 minutes', '+45 seconds', '+1000 microseconds'];

        foreach ($timeModifiers as $modifier) {
            try {
                $date->modify($modifier);
                $this->fail(\sprintf("Expected LogicException for modifier '%s'", $modifier));
            } catch (\LogicException $e) {
                $this->assertStringContainsString(
                    'affects time components',
                    $e->getMessage()
                );
            }
        }
    }

    public function testModifyNormalizesResult(): void
    {
        $date = new DateImmutable('2025-01-15');
        $modified = $date->modify('+1 day');

        // Result should be a new DateImmutable instance
        $this->assertNotSame($date, $modified);
        $this->assertInstanceOf(DateImmutable::class, $modified);

        // Time should still be midnight UTC
        $this->assertSame('00:00:00', self::formatInternal($modified, 'H:i:s'));
        $this->assertSame(0, $modified->getTimezone()->getOffset($modified));
    }

    // ========================================================================
    // Arithmetic Tests
    // ========================================================================

    public function testAddDateInterval(): void
    {
        $date = new DateImmutable('2025-01-15');

        $this->assertSame('2025-01-16', $date->add(new \DateInterval('P1D'))->format('Y-m-d'));
        $this->assertSame('2025-02-15', $date->add(new \DateInterval('P1M'))->format('Y-m-d'));
        $this->assertSame('2026-01-15', $date->add(new \DateInterval('P1Y'))->format('Y-m-d'));
        $this->assertSame('2025-01-22', $date->add(new \DateInterval('P1W'))->format('Y-m-d'));
    }

    public function testSubDateInterval(): void
    {
        $date = new DateImmutable('2025-01-15');

        $this->assertSame('2025-01-14', $date->sub(new \DateInterval('P1D'))->format('Y-m-d'));
        $this->assertSame('2024-12-15', $date->sub(new \DateInterval('P1M'))->format('Y-m-d'));
        $this->assertSame('2024-01-15', $date->sub(new \DateInterval('P1Y'))->format('Y-m-d'));
    }

    public function testAddNormalizesResult(): void
    {
        $date = new DateImmutable('2025-01-15');
        $result = $date->add(new \DateInterval('P1D'));

        $this->assertInstanceOf(DateImmutable::class, $result);
        $this->assertNotSame($date, $result);
        $this->assertSame('00:00:00', self::formatInternal($result, 'H:i:s'));
    }

    public function testIntervalWithTimeComponentNormalized(): void
    {
        $date = new DateImmutable('2025-01-15');

        // Adding time via DateInterval should be normalized away
        $interval = new \DateInterval('P1DT5H30M');
        $result = $date->add($interval);

        // The date should advance by 1 day, time stays at midnight
        $this->assertSame('2025-01-16', $result->format('Y-m-d'));
        $this->assertSame('00:00:00', self::formatInternal($result, 'H:i:s'));
    }

    public function testSetDate(): void
    {
        $date = new DateImmutable('2025-01-15');
        $result = $date->setDate(2030, 6, 20);

        $this->assertSame('2030-06-20', $result->format('Y-m-d'));
        $this->assertInstanceOf(DateImmutable::class, $result);
    }

    public function testSetISODate(): void
    {
        $date = new DateImmutable('2025-01-15');
        // Week 1 of 2025, Monday
        $result = $date->setISODate(2025, 1, 1);

        $this->assertSame('2024-12-30', $result->format('Y-m-d')); // ISO week 1 of 2025 starts in 2024
        $this->assertInstanceOf(DateImmutable::class, $result);
    }

    public function testSetTimestamp(): void
    {
        $date = new DateImmutable('2025-01-15');
        // Timestamp for 2025-06-15 12:00:00 UTC
        $timestamp = 1750075200;
        $result = $date->setTimestamp($timestamp);

        $this->assertSame('2025-06-16', $result->format('Y-m-d')); // Note: normalized to date only
        $this->assertSame('00:00:00', self::formatInternal($result, 'H:i:s'));
    }

    // ========================================================================
    // Comparison Tests
    // ========================================================================

    public function testNativeComparisonOperators(): void
    {
        $date1 = new DateImmutable('2025-01-15');
        $date2 = new DateImmutable('2025-01-20');
        $date3 = new DateImmutable('2025-01-15');

        $this->assertTrue($date1 < $date2);
        $this->assertTrue($date2 > $date1);
        $this->assertTrue($date1 == $date3);
        $this->assertTrue($date1 !== $date3);
        $this->assertTrue($date1 != $date2);
        $this->assertTrue($date1 <= $date2);
        $this->assertTrue($date1 <= $date3);
        $this->assertTrue($date2 >= $date1);
        $this->assertTrue($date3 >= $date1);
    }

    public function testIsSameDateAs(): void
    {
        $date = new DateImmutable('2025-01-15');

        $this->assertTrue($date->isSameDateAs(new DateImmutable('2025-01-15')));
        $this->assertFalse($date->isSameDateAs(new DateImmutable('2025-01-16')));
    }

    public function testIsSameDateAsWithDateTime(): void
    {
        $date = new DateImmutable('2025-01-15');

        // Should compare only the date portion, ignoring time
        $dateTime = new \DateTime('2025-01-15 14:30:45');
        $this->assertTrue($date->isSameDateAs($dateTime));

        $differentDate = new \DateTime('2025-01-16 00:00:00');
        $this->assertFalse($date->isSameDateAs($differentDate));
    }

    public function testIsBefore(): void
    {
        $date = new DateImmutable('2025-01-15');

        $this->assertTrue($date->isBefore(new DateImmutable('2025-01-16')));
        $this->assertFalse($date->isBefore(new DateImmutable('2025-01-15')));
        $this->assertFalse($date->isBefore(new DateImmutable('2025-01-14')));
    }

    public function testIsAfter(): void
    {
        $date = new DateImmutable('2025-01-15');

        $this->assertTrue($date->isAfter(new DateImmutable('2025-01-14')));
        $this->assertFalse($date->isAfter(new DateImmutable('2025-01-15')));
        $this->assertFalse($date->isAfter(new DateImmutable('2025-01-16')));
    }

    // ========================================================================
    // Serialization Tests
    // ========================================================================

    public function testSerializeAndUnserialize(): void
    {
        $date = new DateImmutable('2025-07-04');
        $serialized = serialize($date);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(DateImmutable::class, $unserialized);
        $this->assertSame('2025-07-04', $unserialized->format('Y-m-d'));
        $this->assertSame('00:00:00', self::formatInternal($unserialized, 'H:i:s'));
        $this->assertSame(0, $unserialized->getTimezone()->getOffset($unserialized));
    }

    public function testJsonSerialize(): void
    {
        $date = new DateImmutable('2025-12-25');

        $this->assertSame('"2025-12-25"', json_encode($date));
    }

    public function testToString(): void
    {
        $date = new DateImmutable('2025-11-11');

        $this->assertSame('2025-11-11', (string) $date);
    }

    // ========================================================================
    // Edge Cases
    // ========================================================================

    public function testLeapYear(): void
    {
        // 2024 is a leap year
        $date = new DateImmutable('2024-02-29');
        $this->assertSame('2024-02-29', $date->format('Y-m-d'));

        // Adding 1 year to Feb 29 results in March 1 (PHP's native behavior)
        // since Feb 29 doesn't exist in 2025
        $nextYear = $date->add(new \DateInterval('P1Y'));
        $this->assertSame('2025-03-01', $nextYear->format('Y-m-d'));
    }

    public function testYearBoundary(): void
    {
        $dec31 = new DateImmutable('2025-12-31');
        $jan1 = $dec31->modify('+1 day');

        $this->assertSame('2026-01-01', $jan1->format('Y-m-d'));
    }

    public function testGetMicrosecondAlwaysReturnsZero(): void
    {
        if (PHP_VERSION_ID < 80400) {
            $this->markTestSkipped('A test case for a \DateImmutable method added in PHP 8.4');
        }

        $date = new DateImmutable('2025-01-15');

        $this->assertSame(0, $date->getMicrosecond());
    }

    public function testToMutable(): void
    {
        $immutable = new DateImmutable('2025-01-15');
        $mutable = $immutable->toMutable();

        $this->assertInstanceOf(Date::class, $mutable);
        $this->assertSame('2025-01-15', $mutable->format('Y-m-d'));
    }

    public function testDiff(): void
    {
        $date1 = new DateImmutable('2025-01-15');
        $date2 = new DateImmutable('2025-01-20');

        $diff = $date1->diff($date2);

        $this->assertSame(5, $diff->days);
        $this->assertSame(0, $diff->invert);
    }

    // Helper method to access parent format without validation
    private static function formatInternal(DateImmutable $date, string $format): string
    {
        return (new \ReflectionClass(\DateTimeImmutable::class))
            ->getMethod('format')
            ->invoke($date, $format)
        ;
    }
}
