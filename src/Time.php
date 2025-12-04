<?php

declare(strict_types=1);

namespace JanKlan\SimpleDateTime;

/**
 * A mutable time class representing a time-of-day without date or timezone.
 *
 * This class extends DateTime but restricts functionality to time-only operations.
 * Internally, times are stored with the epoch date (1970-01-01) and UTC timezone to enable
 * proper comparison using PHP's native comparison operators (<, >, ==, etc.).
 *
 * WARNING: Consider using TimeImmutable instead for better predictability.
 * Mutable times can lead to unexpected side effects when passed to other code.
 *
 * @see TimeImmutable for the immutable variant (recommended)
 */
final class Time extends \DateTime implements TimeInterface
{
    use TimeTrait;

    /**
     * Create a new Time from a time string.
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
     * Create a Time representing the current time.
     */
    public static function now(): self
    {
        $now = new \DateTime('now');

        return self::create(
            (int) $now->format('G'),
            (int) $now->format('i'),
            (int) $now->format('s'),
            (int) $now->format('u')
        );
    }

    /**
     * Create a Time from hour, minute, second, and microsecond components.
     *
     * @param int $hour        Hour (0-23)
     * @param int $minute      Minute (0-59)
     * @param int $second      Second (0-59)
     * @param int $microsecond Microsecond (0-999999)
     *
     * @throws \InvalidArgumentException if any component is out of range
     */
    public static function create(int $hour, int $minute, int $second = 0, int $microsecond = 0): self
    {
        if ($hour < 0 || $hour > 23) {
            throw new \InvalidArgumentException(\sprintf('Hour must be between 0 and 23, got %d', $hour));
        }
        if ($minute < 0 || $minute > 59) {
            throw new \InvalidArgumentException(\sprintf('Minute must be between 0 and 59, got %d', $minute));
        }
        if ($second < 0 || $second > 59) {
            throw new \InvalidArgumentException(\sprintf('Second must be between 0 and 59, got %d', $second));
        }
        if ($microsecond < 0 || $microsecond > 999999) {
            throw new \InvalidArgumentException(\sprintf('Microsecond must be between 0 and 999999, got %d', $microsecond));
        }

        return new self(\sprintf('%02d:%02d:%02d.%06d', $hour, $minute, $second, $microsecond));
    }

    /**
     * Create a Time from a time string.
     *
     * @param string $time A time string in H:i:s format or other parseable format
     *
     * @throws \InvalidArgumentException if the string cannot be parsed as a time
     */
    public static function fromString(string $time): self
    {
        try {
            return new self($time);
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException(
                \sprintf('Cannot parse "%s" as a time: %s', $time, $exception->getMessage()),
                previous: $exception
            );
        }
    }

    /**
     * Create a Time from any DateTimeInterface.
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
     * Create a Time from total seconds since midnight.
     *
     * @param int $seconds Seconds since midnight (0-86399)
     *
     * @throws \InvalidArgumentException if seconds is out of range
     */
    public static function fromSeconds(int $seconds): self
    {
        if ($seconds < 0 || $seconds >= 86400) {
            throw new \InvalidArgumentException(
                \sprintf('Seconds must be between 0 and 86399, got %d', $seconds)
            );
        }

        $hour = intdiv($seconds, 3600);
        $minute = intdiv($seconds % 3600, 60);
        $second = $seconds % 60;

        return self::create($hour, $minute, $second);
    }

    /**
     * Create a Time representing midnight (00:00:00).
     */
    public static function midnight(): self
    {
        return self::create(0, 0, 0);
    }

    /**
     * Create a Time representing noon (12:00:00).
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
     * Set the time components (mutable - modifies in place).
     *
     * @param int $hour        Hour (0-23)
     * @param int $minute      Minute (0-59)
     * @param int $second      Second (0-59)
     * @param int $microsecond Microsecond (0-999999)
     */
    public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): static
    {
        if ($hour < 0 || $hour > 23) {
            throw new \InvalidArgumentException(\sprintf('Hour must be between 0 and 23, got %d', $hour));
        }
        if ($minute < 0 || $minute > 59) {
            throw new \InvalidArgumentException(\sprintf('Minute must be between 0 and 59, got %d', $minute));
        }
        if ($second < 0 || $second > 59) {
            throw new \InvalidArgumentException(\sprintf('Second must be between 0 and 59, got %d', $second));
        }
        if ($microsecond < 0 || $microsecond > 999999) {
            throw new \InvalidArgumentException(\sprintf('Microsecond must be between 0 and 999999, got %d', $microsecond));
        }

        parent::setTime($hour, $minute, $second, $microsecond);

        return $this;
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
     */
    public function modify(string $modifier): static
    {
        self::validateModify($modifier);

        parent::modify($modifier);

        // Normalize to epoch date (keep only time component, wrap around midnight)
        $hour = (int) parent::format('G');
        $minute = (int) parent::format('i');
        $second = (int) parent::format('s');
        $microsecond = (int) parent::format('u');

        // Reset to epoch date
        parent::setDate(1970, 1, 1);
        parent::setTime($hour, $minute, $second, $microsecond);

        return $this;
    }

    /**
     * Add an interval to the time (mutable - modifies in place).
     *
     * Note: Only the time portion of the interval is used. Adding P1D has no effect.
     * Time wraps around at midnight.
     */
    public function add(\DateInterval $interval): static
    {
        parent::add($interval);

        // Normalize to epoch date (keep only time component)
        $hour = (int) parent::format('G');
        $minute = (int) parent::format('i');
        $second = (int) parent::format('s');
        $microsecond = (int) parent::format('u');

        parent::setDate(1970, 1, 1);
        parent::setTime($hour, $minute, $second, $microsecond);

        return $this;
    }

    /**
     * Subtract an interval from the time (mutable - modifies in place).
     *
     * Note: Only the time portion of the interval is used. Subtracting P1D has no effect.
     * Time wraps around at midnight.
     */
    public function sub(\DateInterval $interval): static
    {
        parent::sub($interval);

        // Normalize to epoch date (keep only time component)
        $hour = (int) parent::format('G');
        $minute = (int) parent::format('i');
        $second = (int) parent::format('s');
        $microsecond = (int) parent::format('u');

        parent::setDate(1970, 1, 1);
        parent::setTime($hour, $minute, $second, $microsecond);

        return $this;
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
     * Convert to immutable TimeImmutable.
     */
    public function toImmutable(): TimeImmutable
    {
        return TimeImmutable::fromDateTime($this);
    }
}
