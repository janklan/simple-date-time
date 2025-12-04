<?php

declare(strict_types=1);

namespace Janklan\SimpleDateTime;

/**
 * A mutable date class representing a calendar date without time or timezone.
 *
 * This class extends DateTime but restricts functionality to date-only operations.
 * Internally, dates are stored as midnight UTC to enable proper comparison using PHP's
 * native comparison operators (<, >, ==, etc.).
 *
 * WARNING: Consider using DateImmutable instead for better predictability.
 * Mutable dates can lead to unexpected side effects when passed to other code.
 *
 * @see DateImmutable for the immutable variant (recommended)
 */
final class Date extends \DateTime implements DateInterface
{
    use DateTrait;

    /**
     * Create a new Date from a date string.
     *
     * Any time component in the input is ignored; the date is normalized to midnight UTC.
     *
     * @param string $datetime A date string (e.g., "2025-01-15", "today", "next monday")
     * @param null|\DateTimeZone $timezone Used for parsing relative dates (e.g., "today"), but result is normalized to UTC
     *
     * @throws \Exception if the date string cannot be parsed
     */
    public function __construct(string $datetime = 'now', ?\DateTimeZone $timezone = null)
    {
        // First parse the date in the given timezone (or default) to get the correct date
        $parsed = new \DateTime($datetime, $timezone);

        // Then normalize to midnight UTC
        $normalized = self::createMidnightUtcString($parsed->format('Y-m-d'));

        parent::__construct($normalized, new \DateTimeZone('UTC'));
    }

    /**
     * @param array{date: string} $data
     */
    public function __unserialize(array $data): void
    {
        $normalized = self::createMidnightUtcString($data['date']);
        parent::__construct($normalized, new \DateTimeZone('UTC'));
    }

    /**
     * Create a Date representing today's date.
     *
     * @param null|\DateTimeZone $timezone The timezone to use for determining "today"
     */
    public static function today(?\DateTimeZone $timezone = null): self
    {
        return new self('today', $timezone);
    }

    /**
     * Create a Date from a date string.
     *
     * @param string $date A date string in Y-m-d format or other parseable format
     *
     * @throws \InvalidArgumentException if the string cannot be parsed as a date
     */
    public static function fromString(string $date): self
    {
        try {
            return new self($date);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf('Cannot parse "%s" as a date: %s', $date, $e->getMessage()),
                previous: $e
            );
        }
    }

    /**
     * Create a Date from any DateTimeInterface.
     *
     * The time component is ignored; only the date portion is used.
     *
     * @param \DateTimeInterface $dateTime The datetime to extract the date from
     */
    public static function fromDateTime(\DateTimeInterface $dateTime): self
    {
        return new self($dateTime->format('Y-m-d'));
    }

    /**
     * Create a Date from a format string.
     *
     * @param string $format The format of the date string
     * @param string $datetime The date string to parse
     * @param null|\DateTimeZone $timezone Timezone for parsing (date is normalized to UTC)
     *
     * @throws \InvalidArgumentException if the format/datetime combination is invalid
     */
    public static function createFromFormat(
        string $format,
        string $datetime,
        ?\DateTimeZone $timezone = null,
    ): self {
        $parsed = parent::createFromFormat($format, $datetime, $timezone);

        if (false === $parsed) {
            throw new \InvalidArgumentException(
                sprintf('Cannot parse "%s" using format "%s"', $datetime, $format)
            );
        }

        return self::fromDateTime($parsed);
    }

    /**
     * @throws \LogicException Always - time modification is not supported
     */
    public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): static
    {
        throw new \LogicException(
            'setTime() is not supported on Date objects. Date represents a calendar date without time.'
        );
    }

    /**
     * @throws \LogicException Always - timezone modification is not supported
     */
    public function setTimezone(\DateTimeZone $timezone): static
    {
        throw new \LogicException(
            'setTimezone() is not supported on Date objects. Date is timezone-agnostic and stored as midnight UTC.'
        );
    }

    /**
     * Format the date using a date-only format string.
     *
     * @param string $format A format string containing only date-related specifiers
     *
     * @throws \InvalidArgumentException if the format contains time or timezone specifiers
     */
    public function format(string $format): string
    {
        self::validateFormat($format);

        return parent::format($format);
    }

    /**
     * Modify the date using a relative date string.
     *
     * Only date modifiers are allowed (e.g., "+1 day", "+1 month", "next monday").
     * Time modifiers (e.g., "+1 hour") will throw an exception.
     *
     * @param string $modifier A date modification string
     *
     * @throws \LogicException if the modifier affects time components
     */
    public function modify(string $modifier): static
    {
        self::validateModify($modifier);

        parent::modify($modifier);

        // Normalize to midnight UTC
        parent::setTime(0, 0, 0, 0);
        parent::setTimezone(new \DateTimeZone('UTC'));

        return $this;
    }

    /**
     * Add an interval to the date.
     *
     * The result is normalized to midnight UTC.
     */
    public function add(\DateInterval $interval): static
    {
        parent::add($interval);

        // Normalize to midnight UTC
        parent::setTime(0, 0, 0, 0);
        parent::setTimezone(new \DateTimeZone('UTC'));

        return $this;
    }

    /**
     * Subtract an interval from the date.
     *
     * The result is normalized to midnight UTC.
     */
    public function sub(\DateInterval $interval): static
    {
        parent::sub($interval);

        // Normalize to midnight UTC
        parent::setTime(0, 0, 0, 0);
        parent::setTimezone(new \DateTimeZone('UTC'));

        return $this;
    }

    /**
     * Set the date.
     *
     * The time remains normalized to midnight UTC.
     */
    public function setDate(int $year, int $month, int $day): static
    {
        parent::setDate($year, $month, $day);

        // Ensure time stays at midnight UTC
        parent::setTime(0, 0, 0, 0);

        return $this;
    }

    /**
     * Set the ISO date.
     *
     * The time remains normalized to midnight UTC.
     */
    public function setISODate(int $year, int $week, int $dayOfWeek = 1): static
    {
        parent::setISODate($year, $week, $dayOfWeek);

        // Ensure time stays at midnight UTC
        parent::setTime(0, 0, 0, 0);

        return $this;
    }

    /**
     * Set the timestamp.
     *
     * The result is normalized to midnight UTC (only the date portion of the timestamp is used).
     */
    public function setTimestamp(int $timestamp): static
    {
        parent::setTimestamp($timestamp);

        // Normalize to midnight UTC
        parent::setTime(0, 0, 0, 0);
        parent::setTimezone(new \DateTimeZone('UTC'));

        return $this;
    }

    /**
     * Convert to immutable DateImmutable.
     */
    public function toImmutable(): DateImmutable
    {
        return DateImmutable::fromDateTime($this);
    }
}
