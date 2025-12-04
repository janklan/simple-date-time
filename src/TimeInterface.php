<?php

declare(strict_types=1);

namespace JanKlan\SimpleDateTime;

/**
 * Interface for timezone-agnostic time value objects.
 *
 * This interface extends DateTimeInterface to provide a contract for time-only
 * objects that represent time-of-day without date or timezone components.
 */
interface TimeInterface extends \DateTimeInterface, \Stringable, \JsonSerializable
{
    /**
     * Compare only the time portion of two DateTimeInterface objects.
     */
    public function isSameTimeAs(\DateTimeInterface $other): bool;

    /**
     * Check if this time is before another time (ignoring date).
     */
    public function isBefore(\DateTimeInterface $other): bool;

    /**
     * Check if this time is after another time (ignoring date).
     */
    public function isAfter(\DateTimeInterface $other): bool;

    /**
     * Get the hour component (0-23).
     */
    public function getHour(): int;

    /**
     * Get the minute component (0-59).
     */
    public function getMinute(): int;

    /**
     * Get the second component (0-59).
     */
    public function getSecond(): int;
}
