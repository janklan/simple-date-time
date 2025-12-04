<?php

declare(strict_types=1);

namespace Janklan\SimpleDateTime\Tests;

use PHPUnit\Framework\TestCase;
use Janklan\SimpleDateTime\Time;
use Janklan\SimpleDateTime\TimeImmutable;

class TimeImmutableTest extends TestCase
{
    // ========================================================================
    // Construction Tests
    // ========================================================================

    public function testConstructWithTimeString(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->assertSame('14:30:00', $time->format('H:i:s'));
    }

    public function testConstructWithShortTimeString(): void
    {
        $time = new TimeImmutable('14:30');

        $this->assertSame('14:30:00', $time->format('H:i:s'));
    }

    public function testConstructWithAmPmFormat(): void
    {
        $time = new TimeImmutable('2:30pm');

        $this->assertSame('14:30:00', $time->format('H:i:s'));
    }

    public function testConstructNormalizesToEpochDateUtc(): void
    {
        $time = new TimeImmutable('14:30:45');

        // Internal date should be epoch date
        $this->assertSame('1970-01-01', self::formatInternal($time, 'Y-m-d'));
        // Timezone should be UTC
        $this->assertSame(0, $time->getTimezone()->getOffset($time));
    }

    public function testCreateFactory(): void
    {
        $time = TimeImmutable::create(14, 30, 45);

        $this->assertSame(14, $time->getHour());
        $this->assertSame(30, $time->getMinute());
        $this->assertSame(45, $time->getSecond());
    }

    public function testCreateFactoryWithMicroseconds(): void
    {
        $time = TimeImmutable::create(14, 30, 45, 123456);

        $this->assertSame('14:30:45.123456', $time->format('H:i:s.u'));
    }

    public function testCreateFactoryValidatesHour(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Hour must be between 0 and 23');

        TimeImmutable::create(24, 0, 0);
    }

    public function testCreateFactoryValidatesMinute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minute must be between 0 and 59');

        TimeImmutable::create(12, 60, 0);
    }

    public function testCreateFactoryValidatesSecond(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Second must be between 0 and 59');

        TimeImmutable::create(12, 30, 60);
    }

    public function testNowFactory(): void
    {
        $before = new \DateTimeImmutable('now');
        $time = TimeImmutable::now();
        $after = new \DateTimeImmutable('now');

        // Time should be within the range
        $this->assertGreaterThanOrEqual($before->format('H:i:s'), $time->format('H:i:s'));
        $this->assertLessThanOrEqual($after->format('H:i:s'), $time->format('H:i:s'));
    }

    public function testFromStringFactory(): void
    {
        $time = TimeImmutable::fromString('09:15:30');

        $this->assertSame('09:15:30', $time->format('H:i:s'));
    }

    public function testFromStringFactoryThrowsOnInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse "not-a-time" as a time');

        TimeImmutable::fromString('not-a-time');
    }

    public function testFromDateTimeFactory(): void
    {
        $dateTime = new \DateTime('2025-06-15 16:45:30');
        $time = TimeImmutable::fromDateTime($dateTime);

        $this->assertSame('16:45:30', $time->format('H:i:s'));
        // Date should be normalized to epoch
        $this->assertSame('1970-01-01', self::formatInternal($time, 'Y-m-d'));
    }

    public function testFromSecondsFactory(): void
    {
        // 14:30:45 = 14*3600 + 30*60 + 45 = 52245
        $time = TimeImmutable::fromSeconds(52245);

        $this->assertSame('14:30:45', $time->format('H:i:s'));
    }

    public function testFromSecondsFactoryValidatesRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Seconds must be between 0 and 86399');

        TimeImmutable::fromSeconds(86400); // 24 hours
    }

    public function testMidnightFactory(): void
    {
        $time = TimeImmutable::midnight();

        $this->assertSame('00:00:00', $time->format('H:i:s'));
    }

    public function testNoonFactory(): void
    {
        $time = TimeImmutable::noon();

        $this->assertSame('12:00:00', $time->format('H:i:s'));
    }

    // ========================================================================
    // Blocked Operations Tests
    // ========================================================================

    public function testSetDateThrowsLogicException(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setDate() is not supported on Time objects');

        $time->setDate(2025, 1, 15);
    }

    public function testSetISODateThrowsLogicException(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setISODate() is not supported on Time objects');

        $time->setISODate(2025, 1, 1);
    }

    public function testSetTimezoneThrowsLogicException(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setTimezone() is not supported on Time objects');

        $time->setTimezone(new \DateTimeZone('America/New_York'));
    }

    public function testSetTimestampThrowsLogicException(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setTimestamp() is not supported on Time objects');

        $time->setTimestamp(1234567890);
    }

    // ========================================================================
    // Format Validation Tests
    // ========================================================================

    public function testFormatAllowsTimeCharacters(): void
    {
        $time = new TimeImmutable('14:30:45');

        $this->assertSame('14', $time->format('H'));
        $this->assertSame('02', $time->format('h'));
        $this->assertSame('14', $time->format('G'));
        $this->assertSame('2', $time->format('g'));
        $this->assertSame('30', $time->format('i'));
        $this->assertSame('45', $time->format('s'));
        $this->assertSame('pm', $time->format('a'));
        $this->assertSame('PM', $time->format('A'));
    }

    public function testFormatBlocksDateCharacters(): void
    {
        $time = new TimeImmutable('14:30:00');
        $dateChars = ['Y', 'y', 'm', 'n', 'd', 'j', 'D', 'l', 'F', 'M', 'W'];

        foreach ($dateChars as $char) {
            try {
                $time->format($char);
                $this->fail("Expected InvalidArgumentException for format character '{$char}'");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    "Format character \"{$char}\" is not allowed",
                    $e->getMessage()
                );
            }
        }
    }

    public function testFormatBlocksTimezoneCharacters(): void
    {
        $time = new TimeImmutable('14:30:00');
        $tzChars = ['e', 'I', 'O', 'P', 'p', 'T', 'Z'];

        foreach ($tzChars as $char) {
            try {
                $time->format($char);
                $this->fail("Expected InvalidArgumentException for format character '{$char}'");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    "Format character \"{$char}\" is not allowed",
                    $e->getMessage()
                );
            }
        }
    }

    public function testFormatAllowsEscapedCharacters(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->assertSame('Time: 14:30:00 Y-m-d', $time->format('\T\i\m\e: H:i:s \Y-\m-\d'));
    }

    // ========================================================================
    // Modify Validation Tests
    // ========================================================================

    public function testModifyAllowsTimeModifiers(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->assertSame('15:30:00', $time->modify('+1 hour')->format('H:i:s'));
        $this->assertSame('13:30:00', $time->modify('-1 hour')->format('H:i:s'));
        $this->assertSame('14:45:00', $time->modify('+15 minutes')->format('H:i:s'));
        $this->assertSame('14:30:30', $time->modify('+30 seconds')->format('H:i:s'));
    }

    public function testModifyBlocksDateModifiers(): void
    {
        $time = new TimeImmutable('14:30:00');
        $dateModifiers = ['+1 day', '-1 week', '+1 month', '+1 year', 'next monday'];

        foreach ($dateModifiers as $modifier) {
            try {
                $time->modify($modifier);
                $this->fail("Expected LogicException for modifier '{$modifier}'");
            } catch (\LogicException $e) {
                $this->assertStringContainsString(
                    'affects date components',
                    $e->getMessage()
                );
            }
        }
    }

    public function testModifyWrapsAroundMidnight(): void
    {
        $time = new TimeImmutable('23:00:00');
        $result = $time->modify('+2 hours');

        $this->assertSame('01:00:00', $result->format('H:i:s'));
    }

    public function testModifyReturnsNewInstance(): void
    {
        $time = new TimeImmutable('14:30:00');
        $result = $time->modify('+1 hour');

        $this->assertNotSame($time, $result);
        $this->assertSame('14:30:00', $time->format('H:i:s'));
        $this->assertSame('15:30:00', $result->format('H:i:s'));
    }

    // ========================================================================
    // Arithmetic Tests
    // ========================================================================

    public function testAddTimeInterval(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->assertSame('15:30:00', $time->add(new \DateInterval('PT1H'))->format('H:i:s'));
        $this->assertSame('14:45:00', $time->add(new \DateInterval('PT15M'))->format('H:i:s'));
        $this->assertSame('14:30:30', $time->add(new \DateInterval('PT30S'))->format('H:i:s'));
    }

    public function testSubTimeInterval(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->assertSame('13:30:00', $time->sub(new \DateInterval('PT1H'))->format('H:i:s'));
        $this->assertSame('14:15:00', $time->sub(new \DateInterval('PT15M'))->format('H:i:s'));
    }

    public function testAddWrapsAroundMidnight(): void
    {
        $time = new TimeImmutable('23:30:00');
        $result = $time->add(new \DateInterval('PT1H'));

        $this->assertSame('00:30:00', $result->format('H:i:s'));
    }

    public function testSubWrapsAroundMidnight(): void
    {
        $time = new TimeImmutable('00:30:00');
        $result = $time->sub(new \DateInterval('PT1H'));

        $this->assertSame('23:30:00', $result->format('H:i:s'));
    }

    public function testSetTime(): void
    {
        $time = new TimeImmutable('14:30:00');
        $result = $time->setTime(9, 15, 30);

        $this->assertSame('09:15:30', $result->format('H:i:s'));
        $this->assertNotSame($time, $result);
    }

    // ========================================================================
    // Comparison Tests
    // ========================================================================

    public function testNativeComparisonOperators(): void
    {
        $time1 = new TimeImmutable('14:30:00');
        $time2 = new TimeImmutable('16:00:00');
        $time3 = new TimeImmutable('14:30:00');

        $this->assertTrue($time1 < $time2);
        $this->assertTrue($time2 > $time1);
        $this->assertTrue($time1 == $time3);
        $this->assertTrue($time1 != $time2);
    }

    public function testIsSameTimeAs(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->assertTrue($time->isSameTimeAs(new TimeImmutable('14:30:00')));
        $this->assertFalse($time->isSameTimeAs(new TimeImmutable('14:30:01')));
    }

    public function testIsSameTimeAsWithDateTime(): void
    {
        $time = new TimeImmutable('14:30:00');
        $dateTime = new \DateTime('2025-06-15 14:30:00');

        $this->assertTrue($time->isSameTimeAs($dateTime));
    }

    public function testIsBefore(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->assertTrue($time->isBefore(new TimeImmutable('15:00:00')));
        $this->assertFalse($time->isBefore(new TimeImmutable('14:30:00')));
        $this->assertFalse($time->isBefore(new TimeImmutable('14:00:00')));
    }

    public function testIsAfter(): void
    {
        $time = new TimeImmutable('14:30:00');

        $this->assertTrue($time->isAfter(new TimeImmutable('14:00:00')));
        $this->assertFalse($time->isAfter(new TimeImmutable('14:30:00')));
        $this->assertFalse($time->isAfter(new TimeImmutable('15:00:00')));
    }

    // ========================================================================
    // Component Accessor Tests
    // ========================================================================

    public function testGetHour(): void
    {
        $time = new TimeImmutable('14:30:45');
        $this->assertSame(14, $time->getHour());
    }

    public function testGetMinute(): void
    {
        $time = new TimeImmutable('14:30:45');
        $this->assertSame(30, $time->getMinute());
    }

    public function testGetSecond(): void
    {
        $time = new TimeImmutable('14:30:45');
        $this->assertSame(45, $time->getSecond());
    }

    // ========================================================================
    // Serialization Tests
    // ========================================================================

    public function testSerializeAndUnserialize(): void
    {
        $time = new TimeImmutable('14:30:45');
        $serialized = serialize($time);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(TimeImmutable::class, $unserialized);
        $this->assertSame('14:30:45', $unserialized->format('H:i:s'));
    }

    public function testJsonSerialize(): void
    {
        $time = new TimeImmutable('14:30:45');

        $this->assertSame('"14:30:45"', json_encode($time));
    }

    public function testToString(): void
    {
        $time = new TimeImmutable('14:30:45');

        $this->assertSame('14:30:45', (string) $time);
    }

    // ========================================================================
    // Edge Cases
    // ========================================================================

    public function testMidnightBoundary(): void
    {
        $time = new TimeImmutable('00:00:00');
        $this->assertSame('00:00:00', $time->format('H:i:s'));
    }

    public function testEndOfDay(): void
    {
        $time = new TimeImmutable('23:59:59');
        $this->assertSame('23:59:59', $time->format('H:i:s'));
    }

    public function testDiff(): void
    {
        $time1 = new TimeImmutable('14:00:00');
        $time2 = new TimeImmutable('16:30:00');

        $diff = $time1->diff($time2);

        $this->assertSame(2, $diff->h);
        $this->assertSame(30, $diff->i);
    }

    public function testToMutable(): void
    {
        $immutable = new TimeImmutable('14:30:00');
        $mutable = $immutable->toMutable();

        $this->assertInstanceOf(Time::class, $mutable);
        $this->assertSame('14:30:00', $mutable->format('H:i:s'));
    }

    // Helper method to access parent format without validation
    private static function formatInternal(TimeImmutable $time, string $format): string
    {
        return (new \ReflectionClass(\DateTimeImmutable::class))
            ->getMethod('format')
            ->invoke($time, $format);
    }
}
