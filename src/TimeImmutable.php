<?php

declare(strict_types=1);

namespace Janklan\SimpleDateTime;

/**
 * An immutable time class representing a time-of-day without date or timezone.
 *
 * This class extends DateTimeImmutable but restricts functionality to time-only operations.
 * Internally, times are stored with the epoch date (1970-01-01) and UTC timezone to enable
 * proper comparison using PHP's native comparison operators (<, >, ==, etc.).
 *
 * Key behaviors:
 * - Constructor accepts time strings and normalizes to epoch date UTC
 * - setDate() and setTimezone() throw LogicException
 * - format() validates format string to prevent date/timezone leakage
 * - modify() validates modifier to prevent date modifications
 * - Comparison methods work on time portion only
 *
 * @example
 * ```php
 * $time = new TimeImmutable('14:30:00');
 * $time = TimeImmutable::now();
 * $time = TimeImmutable::fromString('14:30');
 * $time = TimeImmutable::create(14, 30, 0);
 * echo $time->format('H:i:s'); // "14:30:00"
 * ```
 */
final class TimeImmutable extends \DateTimeImmutable implements TimeInterface
{
    use TimeTrait;

    /**
     * Create a new TimeImmutable from a time string.
     *
     * Any date component in the input is ignored; the time is normalized to epoch date UTC.
     *
     * @param string $time A time string (e.g., "14:30:00", "14:30", "2:30pm", "now")
     *
     * @throws \Exception if the time string cannot be parsed
     */
    public function __construct(string $time = 'now')
    {
        $parsed = self::parseTimeString($time);
        $normalized = self::createNormalizedTimeString(
            $parsed['hour'],
            $parsed['minute'],
            $parsed['second'],
            $parsed['microsecond']
        );

        parent::__construct($normalized, new \DateTimeZone('UTC'));
    }

    /**
     * @param array{time: string} $data
     */
    public function __unserialize(array $data): void
    {
        $parsed = self::parseTimeString($data['time']);
        $normalized = self::createNormalizedTimeString(
            $parsed['hour'],
            $parsed['minute'],
            $parsed['second'],
            $parsed['microsecond']
        );
        parent::__construct($normalized, new \DateTimeZone('UTC'));
    }

    /**
     * Create a TimeImmutable representing the current time.
     */
    public static function now(): self
    {
        $now = new \DateTimeImmutable('now');

        return self::create(
            (int) $now->format('G'),
            (int) $now->format('i'),
            (int) $now->format('s'),
            (int) $now->format('u')
        );
    }

    /**
     * Create a TimeImmutable from hour, minute, second, and microsecond components.
     *
     * @param int $hour Hour (0-23)
     * @param int $minute Minute (0-59)
     * @param int $second Second (0-59)
     * @param int $microsecond Microsecond (0-999999)
     *
     * @throws \InvalidArgumentException if any component is out of range
     */
    public static function create(int $hour, int $minute, int $second = 0, int $microsecond = 0): self
    {
        if ($hour < 0 || $hour > 23) {
            throw new \InvalidArgumentException(sprintf('Hour must be between 0 and 23, got %d', $hour));
        }
        if ($minute < 0 || $minute > 59) {
            throw new \InvalidArgumentException(sprintf('Minute must be between 0 and 59, got %d', $minute));
        }
        if ($second < 0 || $second > 59) {
            throw new \InvalidArgumentException(sprintf('Second must be between 0 and 59, got %d', $second));
        }
        if ($microsecond < 0 || $microsecond > 999999) {
            throw new \InvalidArgumentException(sprintf('Microsecond must be between 0 and 999999, got %d', $microsecond));
        }

        return new self(sprintf('%02d:%02d:%02d.%06d', $hour, $minute, $second, $microsecond));
    }

    /**
     * Create a TimeImmutable from a time string.
     *
     * @param string $time A time string in H:i:s format or other parseable format
     *
     * @throws \InvalidArgumentException if the string cannot be parsed as a time
     */
    public static function fromString(string $time): self
    {
        try {
            return new self($time);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf('Cannot parse "%s" as a time: %s', $time, $e->getMessage()),
                previous: $e
            );
        }
    }

    /**
     * Create a TimeImmutable from any DateTimeInterface.
     *
     * The date component is ignored; only the time portion is used.
     *
     * @param \DateTimeInterface $dateTime The datetime to extract the time from
     */
    public static function fromDateTime(\DateTimeInterface $dateTime): self
    {
        return self::create(
            (int) $dateTime->format('G'),
            (int) $dateTime->format('i'),
            (int) $dateTime->format('s'),
            (int) $dateTime->format('u')
        );
    }

    /**
     * Create a TimeImmutable from total seconds since midnight.
     *
     * @param int $seconds Seconds since midnight (0-86399)
     *
     * @throws \InvalidArgumentException if seconds is out of range
     */
    public static function fromSeconds(int $seconds): self
    {
        if ($seconds < 0 || $seconds >= 86400) {
            throw new \InvalidArgumentException(
                sprintf('Seconds must be between 0 and 86399, got %d', $seconds)
            );
        }

        $hour = intdiv($seconds, 3600);
        $minute = intdiv($seconds % 3600, 60);
        $second = $seconds % 60;

        return self::create($hour, $minute, $second);
    }

    /**
     * Create a TimeImmutable representing midnight (00:00:00).
     */
    public static function midnight(): self
    {
        return self::create(0, 0, 0);
    }

    /**
     * Create a TimeImmutable representing noon (12:00:00).
     */
    public static function noon(): self
    {
        return self::create(12, 0, 0);
    }

    /**
     * @throws \LogicException Always - date modification is not supported
     */
    public function setDate(int $year, int $month, int $day): static
    {
        throw new \LogicException(
            'setDate() is not supported on Time objects. Time represents a time-of-day without date.'
        );
    }

    /**
     * @throws \LogicException Always - ISO date modification is not supported
     */
    public function setISODate(int $year, int $week, int $dayOfWeek = 1): static
    {
        throw new \LogicException(
            'setISODate() is not supported on Time objects. Time represents a time-of-day without date.'
        );
    }

    /**
     * @throws \LogicException Always - timezone modification is not supported
     */
    public function setTimezone(\DateTimeZone $timezone): static
    {
        throw new \LogicException(
            'setTimezone() is not supported on Time objects. Time is timezone-agnostic and stored with UTC.'
        );
    }

    /**
     * @throws \LogicException Always - timestamp modification is not supported (includes date)
     */
    public function setTimestamp(int $timestamp): static
    {
        throw new \LogicException(
            'setTimestamp() is not supported on Time objects. Use setTime() or create() instead.'
        );
    }

    /**
     * Set the time components.
     *
     * @param int $hour Hour (0-23)
     * @param int $minute Minute (0-59)
     * @param int $second Second (0-59)
     * @param int $microsecond Microsecond (0-999999)
     */
    public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): static
    {
        return self::create($hour, $minute, $second, $microsecond);
    }

    /**
     * Format the time using a time-only format string.
     *
     * @param string $format A format string containing only time-related specifiers
     *
     * @throws \InvalidArgumentException if the format contains date or timezone specifiers
     */
    public function format(string $format): string
    {
        self::validateFormat($format);

        return parent::format($format);
    }

    /**
     * Modify the time using a relative time string.
     *
     * Only time modifiers are allowed (e.g., "+1 hour", "+30 minutes", "-15 seconds").
     * Date modifiers (e.g., "+1 day") will throw an exception.
     *
     * Note: Time wraps around at midnight. Adding 2 hours to 23:00 gives 01:00.
     *
     * @param string $modifier A time modification string
     *
     * @throws \LogicException if the modifier affects date components
     * @throws \Exception if the modifier string is invalid
     */
    public function modify(string $modifier): static
    {
        self::validateModify($modifier);

        return self::fromDateTime(parent::modify($modifier));
    }

    /**
     * Add an interval to the time.
     *
     * Note: Only the time portion of the interval is used. Adding P1D has no effect.
     * Time wraps around at midnight.
     */
    public function add(\DateInterval $interval): static
    {
        $result = parent::add($interval);

        // Normalize to epoch date (keep only time component)
        return self::fromDateTime($result);
    }

    /**
     * Subtract an interval from the time.
     *
     * Note: Only the time portion of the interval is used. Subtracting P1D has no effect.
     * Time wraps around at midnight.
     */
    public function sub(\DateInterval $interval): static
    {
        $result = parent::sub($interval);

        // Normalize to epoch date (keep only time component)
        return self::fromDateTime($result);
    }

    /**
     * Get the difference between this time and another as a DateInterval.
     *
     * Note: This compares times only, ignoring any date component in $other.
     */
    public function diff(\DateTimeInterface $targetObject, bool $absolute = false): \DateInterval
    {
        // Normalize both to same date for accurate time diff
        $thisNormalized = new \DateTimeImmutable(
            self::EPOCH_DATE.' '.parent::format('H:i:s.u'),
            new \DateTimeZone('UTC')
        );

        $otherNormalized = new \DateTimeImmutable(
            self::EPOCH_DATE.' '.$targetObject->format('H:i:s.u'),
            new \DateTimeZone('UTC')
        );

        return $thisNormalized->diff($otherNormalized, $absolute);
    }

    /**
     * Convert to the mutable Time class.
     */
    public function toMutable(): Time
    {
        return Time::fromDateTime($this);
    }
}
