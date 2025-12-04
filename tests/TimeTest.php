<?php

declare(strict_types=1);

namespace Janklan\SimpleDateTime\Tests;

use PHPUnit\Framework\TestCase;
use Janklan\SimpleDateTime\Time;
use Janklan\SimpleDateTime\TimeImmutable;

class TimeTest extends TestCase
{
    // ========================================================================
    // Construction Tests
    // ========================================================================

    public function testConstructWithTimeString(): void
    {
        $time = new Time('14:30:00');

        $this->assertSame('14:30:00', $time->format('H:i:s'));
    }

    public function testConstructWithShortTimeString(): void
    {
        $time = new Time('14:30');

        $this->assertSame('14:30:00', $time->format('H:i:s'));
    }

    public function testConstructNormalizesToEpochDateUtc(): void
    {
        $time = new Time('14:30:45');

        // Internal date should be epoch date
        $this->assertSame('1970-01-01', self::formatInternal($time, 'Y-m-d'));
        // Timezone should be UTC
        $this->assertSame(0, $time->getTimezone()->getOffset($time));
    }

    public function testCreateFactory(): void
    {
        $time = Time::create(14, 30, 45);

        $this->assertSame(14, $time->getHour());
        $this->assertSame(30, $time->getMinute());
        $this->assertSame(45, $time->getSecond());
    }

    public function testCreateFactoryValidatesHour(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Hour must be between 0 and 23');

        Time::create(24, 0, 0);
    }

    public function testNowFactory(): void
    {
        $before = new \DateTime('now');
        $time = Time::now();
        $after = new \DateTime('now');

        $this->assertGreaterThanOrEqual($before->format('H:i:s'), $time->format('H:i:s'));
        $this->assertLessThanOrEqual($after->format('H:i:s'), $time->format('H:i:s'));
    }

    public function testFromStringFactory(): void
    {
        $time = Time::fromString('09:15:30');

        $this->assertSame('09:15:30', $time->format('H:i:s'));
    }

    public function testFromStringFactoryThrowsOnInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse "not-a-time" as a time');

        Time::fromString('not-a-time');
    }

    public function testFromDateTimeFactory(): void
    {
        $dateTime = new \DateTime('2025-06-15 16:45:30');
        $time = Time::fromDateTime($dateTime);

        $this->assertSame('16:45:30', $time->format('H:i:s'));
    }

    public function testFromSecondsFactory(): void
    {
        $time = Time::fromSeconds(52245);
        $this->assertSame('14:30:45', $time->format('H:i:s'));
    }

    public function testMidnightFactory(): void
    {
        $time = Time::midnight();
        $this->assertSame('00:00:00', $time->format('H:i:s'));
    }

    public function testNoonFactory(): void
    {
        $time = Time::noon();
        $this->assertSame('12:00:00', $time->format('H:i:s'));
    }

    // ========================================================================
    // Blocked Operations Tests
    // ========================================================================

    public function testSetDateThrowsLogicException(): void
    {
        $time = new Time('14:30:00');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setDate() is not supported on Time objects');

        $time->setDate(2025, 1, 15);
    }

    public function testSetISODateThrowsLogicException(): void
    {
        $time = new Time('14:30:00');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setISODate() is not supported on Time objects');

        $time->setISODate(2025, 1, 1);
    }

    public function testSetTimezoneThrowsLogicException(): void
    {
        $time = new Time('14:30:00');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setTimezone() is not supported on Time objects');

        $time->setTimezone(new \DateTimeZone('America/New_York'));
    }

    public function testSetTimestampThrowsLogicException(): void
    {
        $time = new Time('14:30:00');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setTimestamp() is not supported on Time objects');

        $time->setTimestamp(1234567890);
    }

    // ========================================================================
    // Format Validation Tests
    // ========================================================================

    public function testFormatAllowsTimeCharacters(): void
    {
        $time = new Time('14:30:45');

        $this->assertSame('14', $time->format('H'));
        $this->assertSame('30', $time->format('i'));
        $this->assertSame('45', $time->format('s'));
        $this->assertSame('PM', $time->format('A'));
    }

    public function testFormatBlocksDateCharacters(): void
    {
        $time = new Time('14:30:00');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Format character "Y" is not allowed');

        $time->format('Y-m-d');
    }

    public function testFormatBlocksTimezoneCharacters(): void
    {
        $time = new Time('14:30:00');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Format character "e" is not allowed');

        $time->format('e');
    }

    public function testFormatAllowsEscapedCharacters(): void
    {
        $time = new Time('14:30:00');

        $this->assertSame('Time: 14:30:00', $time->format('\T\i\m\e: H:i:s'));
    }

    // ========================================================================
    // Modify Validation Tests
    // ========================================================================

    public function testModifyAllowsTimeModifiers(): void
    {
        $time = new Time('14:30:00');
        $result = $time->modify('+1 hour');

        $this->assertSame($time, $result); // Mutable - returns self
        $this->assertSame('15:30:00', $time->format('H:i:s'));
    }

    public function testModifyBlocksDateModifiers(): void
    {
        $time = new Time('14:30:00');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('affects date components');

        $time->modify('+1 day');
    }

    public function testMutableModifyReturnsSelf(): void
    {
        $time = new Time('14:30:00');
        $result = $time->modify('+1 hour');

        $this->assertSame($time, $result);
        $this->assertSame('15:30:00', $time->format('H:i:s'));
    }

    public function testModifyWrapsAroundMidnight(): void
    {
        $time = new Time('23:00:00');
        $time->modify('+2 hours');

        $this->assertSame('01:00:00', $time->format('H:i:s'));
    }

    // ========================================================================
    // Arithmetic Tests
    // ========================================================================

    public function testAddTimeInterval(): void
    {
        $time = new Time('14:30:00');
        $result = $time->add(new \DateInterval('PT1H'));

        $this->assertSame($time, $result); // Mutable - returns self
        $this->assertSame('15:30:00', $time->format('H:i:s'));
    }

    public function testSubTimeInterval(): void
    {
        $time = new Time('14:30:00');
        $result = $time->sub(new \DateInterval('PT1H'));

        $this->assertSame($time, $result);
        $this->assertSame('13:30:00', $time->format('H:i:s'));
    }

    public function testAddWrapsAroundMidnight(): void
    {
        $time = new Time('23:30:00');
        $time->add(new \DateInterval('PT1H'));

        $this->assertSame('00:30:00', $time->format('H:i:s'));
    }

    public function testSetTime(): void
    {
        $time = new Time('14:30:00');
        $result = $time->setTime(9, 15, 30);

        $this->assertSame($time, $result); // Mutable
        $this->assertSame('09:15:30', $time->format('H:i:s'));
    }

    public function testSetTimeValidatesHour(): void
    {
        $time = new Time('14:30:00');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Hour must be between 0 and 23');

        $time->setTime(25, 0, 0);
    }

    // ========================================================================
    // Comparison Tests
    // ========================================================================

    public function testNativeComparisonOperators(): void
    {
        $time1 = new Time('14:30:00');
        $time2 = new Time('16:00:00');
        $time3 = new Time('14:30:00');

        $this->assertTrue($time1 < $time2);
        $this->assertTrue($time2 > $time1);
        $this->assertTrue($time1 == $time3);
        $this->assertTrue($time1 != $time2);
    }

    public function testIsSameTimeAs(): void
    {
        $time = new Time('14:30:00');

        $this->assertTrue($time->isSameTimeAs(new Time('14:30:00')));
        $this->assertFalse($time->isSameTimeAs(new Time('14:30:01')));
    }

    public function testIsBefore(): void
    {
        $time = new Time('14:30:00');

        $this->assertTrue($time->isBefore(new Time('15:00:00')));
        $this->assertFalse($time->isBefore(new Time('14:30:00')));
        $this->assertFalse($time->isBefore(new Time('14:00:00')));
    }

    public function testIsAfter(): void
    {
        $time = new Time('14:30:00');

        $this->assertTrue($time->isAfter(new Time('14:00:00')));
        $this->assertFalse($time->isAfter(new Time('14:30:00')));
        $this->assertFalse($time->isAfter(new Time('15:00:00')));
    }

    // ========================================================================
    // Component Accessor Tests
    // ========================================================================

    public function testGetHour(): void
    {
        $time = new Time('14:30:45');
        $this->assertSame(14, $time->getHour());
    }

    public function testGetMinute(): void
    {
        $time = new Time('14:30:45');
        $this->assertSame(30, $time->getMinute());
    }

    public function testGetSecond(): void
    {
        $time = new Time('14:30:45');
        $this->assertSame(45, $time->getSecond());
    }

    // ========================================================================
    // Serialization Tests
    // ========================================================================

    public function testSerializeAndUnserialize(): void
    {
        $time = new Time('14:30:45');
        $serialized = serialize($time);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(Time::class, $unserialized);
        $this->assertSame('14:30:45', $unserialized->format('H:i:s'));
    }

    public function testJsonSerialize(): void
    {
        $time = new Time('14:30:45');

        $this->assertSame('"14:30:45"', json_encode($time));
    }

    public function testToString(): void
    {
        $time = new Time('14:30:45');

        $this->assertSame('14:30:45', (string) $time);
    }

    // ========================================================================
    // Conversion Tests
    // ========================================================================

    public function testToImmutable(): void
    {
        $mutable = new Time('14:30:00');
        $immutable = $mutable->toImmutable();

        $this->assertInstanceOf(TimeImmutable::class, $immutable);
        $this->assertSame('14:30:00', $immutable->format('H:i:s'));
    }

    public function testToImmutableDoesNotAffectOriginal(): void
    {
        $mutable = new Time('14:30:00');
        $immutable = $mutable->toImmutable();

        $mutable->modify('+1 hour');

        $this->assertSame('15:30:00', $mutable->format('H:i:s'));
        $this->assertSame('14:30:00', $immutable->format('H:i:s'));
    }

    // ========================================================================
    // Edge Cases
    // ========================================================================

    public function testMidnightBoundary(): void
    {
        $time = new Time('00:00:00');
        $this->assertSame('00:00:00', $time->format('H:i:s'));
    }

    public function testEndOfDay(): void
    {
        $time = new Time('23:59:59');
        $this->assertSame('23:59:59', $time->format('H:i:s'));
    }

    public function testDiff(): void
    {
        $time1 = new Time('14:00:00');
        $time2 = new Time('16:30:00');

        $diff = $time1->diff($time2);

        $this->assertSame(2, $diff->h);
        $this->assertSame(30, $diff->i);
    }

    // Helper method to access parent format without validation
    private static function formatInternal(Time $time, string $format): string
    {
        return (new \ReflectionClass(\DateTime::class))
            ->getMethod('format')
            ->invoke($time, $format);
    }
}
