<?php

declare(strict_types=1);

namespace JanKlan\SimpleDateTime;

/**
 * Interface for timezone-agnostic date value objects.
 *
 * This interface extends DateTimeInterface to provide a contract for date-only
 * objects that represent calendar dates without time or timezone components.
 */
interface DateInterface extends \DateTimeInterface, \Stringable, \JsonSerializable
{
    /**
     * Compare only the date portion (Y-m-d) of two DateTimeInterface objects.
     */
    public function isSameDateAs(\DateTimeInterface $other): bool;

    /**
     * Check if this date is before another date (ignoring time).
     */
    public function isBefore(\DateTimeInterface $other): bool;

    /**
     * Check if this date is after another date (ignoring time).
     */
    public function isAfter(\DateTimeInterface $other): bool;
}
