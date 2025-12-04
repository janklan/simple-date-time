<?php

declare(strict_types=1);

namespace JanKlan\SimpleDateTime;

/**
 * Shared functionality for timezone-agnostic Date classes.
 *
 * This trait provides validation logic, comparison methods, and serialization
 * for Date and DateImmutable classes that represent calendar dates without
 * time or timezone components.
 */
trait DateTrait
{
    /**
     * Format characters blocked because they expose time information.
     */
    private const array BLOCKED_TIME_FORMAT_CHARS = [
        'a', // Lowercase am/pm
        'A', // Uppercase AM/PM
        'B', // Swatch Internet time
        'g', // 12-hour format without leading zeros
        'G', // 24-hour format without leading zeros
        'h', // 12-hour format with leading zeros
        'H', // 24-hour format with leading zeros
        'i', // Minutes with leading zeros
        's', // Seconds with leading zeros
        'u', // Microseconds
        'v', // Milliseconds
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
     * Composite format characters that include time/timezone.
     */
    private const array BLOCKED_COMPOSITE_FORMAT_CHARS = [
        'c', // ISO 8601 date (includes time and timezone)
        'r', // RFC 2822 formatted date (includes time and timezone)
        'U', // Seconds since Unix Epoch (exposes time precision)
    ];

    /**
     * Time-related keywords in modify() strings that should be blocked.
     */
    private const array BLOCKED_MODIFY_KEYWORDS = [
        'hour',
        'hours',
        'minute',
        'minutes',
        'second',
        'seconds',
        'microsecond',
        'microseconds',
        'msec',
        'millisecond',
        'milliseconds',
        'usec',
        'noon',
    ];

    /**
     * Serialize to Y-m-d format.
     *
     * @return array{date: string}
     */
    public function __serialize(): array
    {
        return ['date' => parent::format('Y-m-d')];
    }

    /**
     * @return string Y-m-d formatted date
     */
    public function __toString(): string
    {
        return parent::format('Y-m-d');
    }

    /**
     * Compare only the date portion (Y-m-d) of two DateTimeInterface objects.
     */
    public function isSameDateAs(\DateTimeInterface $other): bool
    {
        return $this->format('Y-m-d') === $other->format('Y-m-d');
    }

    /**
     * Check if this date is before another date (ignoring time).
     */
    public function isBefore(\DateTimeInterface $other): bool
    {
        return $this->format('Y-m-d') < $other->format('Y-m-d');
    }

    /**
     * Check if this date is after another date (ignoring time).
     */
    public function isAfter(\DateTimeInterface $other): bool
    {
        return $this->format('Y-m-d') > $other->format('Y-m-d');
    }

    /**
     * JSON representation returns the Y-m-d string.
     */
    public function jsonSerialize(): string
    {
        return parent::format('Y-m-d');
    }

    /**
     * Validate format string for date-only output.
     *
     * @throws \InvalidArgumentException if format contains time or timezone specifiers
     */
    protected static function validateFormat(string $format): void
    {
        $blockedChars = array_merge(
            self::BLOCKED_TIME_FORMAT_CHARS,
            self::BLOCKED_TIMEZONE_FORMAT_CHARS,
            self::BLOCKED_COMPOSITE_FORMAT_CHARS
        );

        $length = \strlen($format);
        for ($i = 0; $i < $length; ++$i) {
            $char = $format[$i];

            // Skip escaped characters (backslash escaping)
            if ('\\' === $char && $i + 1 < $length) {
                ++$i; // Skip next character

                continue;
            }

            if (\in_array($char, $blockedChars, true)) {
                throw new \InvalidArgumentException(
                    \sprintf(
                        'Format character "%s" is not allowed for Date. Date objects do not support time or timezone formatting.',
                        $char
                    )
                );
            }
        }
    }

    /**
     * Validate modify string to ensure it doesn't affect time components.
     *
     * @throws \LogicException if modifier affects time components
     */
    protected static function validateModify(string $modifier): void
    {
        $lowerModifier = strtolower($modifier);

        foreach (self::BLOCKED_MODIFY_KEYWORDS as $keyword) {
            // Match whole words only using word boundaries
            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/', $lowerModifier)) {
                throw new \LogicException(
                    \sprintf(
                        'Modifier "%s" affects time components, which is not allowed for Date. '
                        .'Date objects only support date modifications (e.g., "+1 day", "+1 month", "next monday").',
                        $modifier
                    )
                );
            }
        }
    }

    /**
     * Create midnight UTC datetime string from a date string (Y-m-d format).
     */
    protected static function createMidnightUtcString(string $dateString): string
    {
        return $dateString.'T00:00:00+00:00';
    }
}
