<?php

declare(strict_types=1);

namespace Janklan\SimpleDateTime;

/**
 * Shared functionality for timezone-agnostic Time classes.
 *
 * This trait provides validation logic, comparison methods, and serialization
 * for Time and TimeImmutable classes that represent time-of-day without
 * date or timezone components.
 */
trait TimeTrait
{
    /**
     * The epoch date used internally to store time values.
     * Using 1970-01-01 (Unix epoch) as a neutral reference date.
     */
    private const string EPOCH_DATE = '1970-01-01';

    /**
     * Format characters allowed for time-only formatting.
     */
    private const array ALLOWED_TIME_FORMAT_CHARS = [
        // Time
        'a', // Lowercase am/pm
        'A', // Uppercase AM/PM
        'B', // Swatch Internet time
        'g', // 12-hour format without leading zeros (1-12)
        'G', // 24-hour format without leading zeros (0-23)
        'h', // 12-hour format with leading zeros (01-12)
        'H', // 24-hour format with leading zeros (00-23)
        'i', // Minutes with leading zeros (00-59)
        's', // Seconds with leading zeros (00-59)
        'u', // Microseconds (000000-999999)
        'v', // Milliseconds (000-999)
    ];

    /**
     * Format characters blocked because they expose date information.
     */
    private const array BLOCKED_DATE_FORMAT_CHARS = [
        'd', // Day of the month with leading zeros (01-31)
        'D', // Day name abbreviation (Mon-Sun)
        'j', // Day of the month without leading zeros (1-31)
        'l', // Full day name (Sunday-Saturday)
        'N', // ISO-8601 day of week (1=Monday, 7=Sunday)
        'S', // English ordinal suffix (st, nd, rd, th)
        'w', // Day of week (0=Sunday, 6=Saturday)
        'z', // Day of year (0-365)
        'W', // ISO-8601 week number
        'F', // Full month name (January-December)
        'm', // Month with leading zeros (01-12)
        'M', // Month abbreviation (Jan-Dec)
        'n', // Month without leading zeros (1-12)
        't', // Days in month (28-31)
        'L', // Leap year (0 or 1)
        'o', // ISO-8601 week-numbering year
        'X', // Expanded year representation
        'x', // Expanded year if required
        'Y', // 4-digit year
        'y', // 2-digit year
    ];

    /**
     * Format characters blocked because they expose timezone information.
     */
    private const array BLOCKED_TIMEZONE_FORMAT_CHARS = [
        'e', // Timezone identifier
        'I', // DST indicator
        'O', // Timezone offset (+0200)
        'P', // Timezone offset with colon (+02:00)
        'p', // Same as P but Z for UTC
        'T', // Timezone abbreviation
        'Z', // Timezone offset in seconds
    ];

    /**
     * Composite format characters that include date/timezone.
     */
    private const array BLOCKED_COMPOSITE_FORMAT_CHARS = [
        'c', // ISO 8601 date (includes date and timezone)
        'r', // RFC 2822 formatted date (includes date and timezone)
        'U', // Seconds since Unix Epoch (includes date)
    ];

    /**
     * Date-related keywords in modify() strings that should be blocked.
     */
    private const array BLOCKED_MODIFY_KEYWORDS = [
        'day',
        'days',
        'week',
        'weeks',
        'month',
        'months',
        'year',
        'years',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'january',
        'february',
        'march',
        'april',
        'may',
        'june',
        'july',
        'august',
        'september',
        'october',
        'november',
        'december',
        'first',
        'last',
        'next',
        'previous',
        'this',
    ];

    /**
     * Serialize to H:i:s format.
     *
     * @return array{time: string}
     */
    public function __serialize(): array
    {
        return ['time' => parent::format('H:i:s.u')];
    }

    /**
     * @return string H:i:s formatted time
     */
    public function __toString(): string
    {
        return parent::format('H:i:s');
    }

    /**
     * Compare only the time portion of two DateTimeInterface objects.
     */
    public function isSameTimeAs(\DateTimeInterface $other): bool
    {
        return $this->format('H:i:s.u') === $other->format('H:i:s.u');
    }

    /**
     * Check if this time is before another time (ignoring date).
     */
    public function isBefore(\DateTimeInterface $other): bool
    {
        return $this->format('H:i:s.u') < $other->format('H:i:s.u');
    }

    /**
     * Check if this time is after another time (ignoring date).
     */
    public function isAfter(\DateTimeInterface $other): bool
    {
        return $this->format('H:i:s.u') > $other->format('H:i:s.u');
    }

    /**
     * Get the hour component (0-23).
     */
    public function getHour(): int
    {
        return (int) parent::format('G');
    }

    /**
     * Get the minute component (0-59).
     */
    public function getMinute(): int
    {
        return (int) parent::format('i');
    }

    /**
     * Get the second component (0-59).
     */
    public function getSecond(): int
    {
        return (int) parent::format('s');
    }

    /**
     * JSON representation returns the H:i:s string.
     */
    public function jsonSerialize(): string
    {
        return parent::format('H:i:s');
    }

    /**
     * Validate format string for time-only output.
     *
     * @throws \InvalidArgumentException if format contains date or timezone specifiers
     */
    protected static function validateFormat(string $format): void
    {
        $blockedChars = array_merge(
            self::BLOCKED_DATE_FORMAT_CHARS,
            self::BLOCKED_TIMEZONE_FORMAT_CHARS,
            self::BLOCKED_COMPOSITE_FORMAT_CHARS
        );

        $length = strlen($format);
        for ($i = 0; $i < $length; ++$i) {
            $char = $format[$i];

            // Skip escaped characters (backslash escaping)
            if ('\\' === $char && $i + 1 < $length) {
                ++$i; // Skip next character

                continue;
            }

            if (in_array($char, $blockedChars, true)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Format character "%s" is not allowed. Allowed: %s',
                        $char,
                        implode(', ', self::ALLOWED_TIME_FORMAT_CHARS)
                    )
                );
            }
        }
    }

    /**
     * Validate modify string to ensure it doesn't affect date components.
     *
     * @throws \LogicException if modifier affects date components
     */
    protected static function validateModify(string $modifier): void
    {
        $lowerModifier = strtolower($modifier);

        foreach (self::BLOCKED_MODIFY_KEYWORDS as $keyword) {
            // Match whole words only using word boundaries
            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/', $lowerModifier)) {
                throw new \LogicException(
                    sprintf(
                        'Modifier "%s" affects date components, which is not allowed for Time. '
                        .'Time objects only support time modifications (e.g., "+1 hour", "+30 minutes", "+45 seconds").',
                        $modifier
                    )
                );
            }
        }
    }

    /**
     * Create a normalized datetime string with epoch date and UTC timezone.
     */
    protected static function createNormalizedTimeString(int $hour, int $minute, int $second = 0, int $microsecond = 0): string
    {
        return sprintf(
            '%sT%02d:%02d:%02d.%06d+00:00',
            self::EPOCH_DATE,
            $hour,
            $minute,
            $second,
            $microsecond
        );
    }

    /**
     * Parse a time string and extract hours, minutes, seconds, microseconds.
     *
     * @return array{hour: int, minute: int, second: int, microsecond: int}
     */
    protected static function parseTimeString(string $time): array
    {
        // Try parsing as H:i:s.u format first
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?(?:\.(\d+))?$/', $time, $matches)) {
            return [
                'hour' => (int) $matches[1],
                'minute' => (int) $matches[2],
                'second' => isset($matches[3]) ? (int) $matches[3] : 0,
                'microsecond' => isset($matches[4]) ? (int) str_pad($matches[4], 6, '0') : 0,
            ];
        }

        // Fall back to DateTime parsing
        $parsed = new \DateTime($time);

        return [
            'hour' => (int) $parsed->format('G'),
            'minute' => (int) $parsed->format('i'),
            'second' => (int) $parsed->format('s'),
            'microsecond' => (int) $parsed->format('u'),
        ];
    }
}
