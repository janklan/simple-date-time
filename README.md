# Simple Date Time

Timezone-agnostic Date and Time value objects for PHP.

## Why?

PHP's `DateTime` and `DateTimeImmutable` are powerful but carry timezone complexity that's unnecessary for many use cases:

- **Birthdays** - A birthday is January 15th, not "January 15th in UTC-5"
- **Public holidays** - Christmas is December 25th everywhere
- **Business hours** - A store opens at 9:00 AM, not "9:00 AM UTC"
- **Scheduling** - A weekly meeting is on Monday at 2:00 PM

This package provides `DateImmutable`, `Date`, `TimeImmutable`, and `Time` classes that represent pure calendar dates and wall-clock times without timezone baggage.

## Installation

```bash
composer require janklan/simple-date-time
```

## Usage

### Date

```php
use Janklan\SimpleDateTime\DateImmutable;

$birthday = new DateImmutable('1990-05-15');
$today = DateImmutable::today();
$christmas = DateImmutable::fromString('2025-12-25');

// Comparisons
$today->isBefore($christmas);    // true
$today->isAfter($birthday);      // true
$today->isSameDateAs($birthday); // false

// Arithmetic
$nextWeek = $today->modify('+1 week');
$tomorrow = $today->add(new DateInterval('P1D'));

// Formatting (date characters only)
echo $birthday->format('Y-m-d'); // "1990-05-15"
echo $birthday->format('l, F j, Y'); // "Tuesday, May 15, 1990"

// Serialization
echo json_encode($birthday); // "1990-05-15"
echo (string) $birthday;     // "1990-05-15"
```

### Time

```php
use Janklan\SimpleDateTime\TimeImmutable;

$opening = new TimeImmutable('09:00:00');
$closing = TimeImmutable::create(17, 30, 0);
$now = TimeImmutable::now();
$midnight = TimeImmutable::midnight();
$noon = TimeImmutable::noon();

// Comparisons
$now->isBefore($closing);    // true (during business hours)
$now->isAfter($opening);     // true
$now->isSameTimeAs($noon);   // false

// Components
$opening->getHour();   // 9
$opening->getMinute(); // 0
$opening->getSecond(); // 0

// Arithmetic (wraps at midnight)
$lateNight = TimeImmutable::create(23, 0, 0);
$earlyMorning = $lateNight->modify('+2 hours'); // 01:00:00

// Formatting (time characters only)
echo $opening->format('H:i');   // "09:00"
echo $opening->format('g:i A'); // "9:00 AM"
```

### Mutable Variants

Both `Date` and `Time` mutable variants are available, though immutable versions are recommended:

```php
use Janklan\SimpleDateTime\Date;
use Janklan\SimpleDateTime\Time;

$date = new Date('2025-01-15');
$date->modify('+1 day'); // Modifies in place

$time = new Time('14:30:00');
$time->setTime(15, 0, 0); // Modifies in place

// Convert between variants
$immutableDate = $date->toImmutable();
$mutableDate = $immutableDate->toMutable();
```

### Blocked Operations

Operations that would leak time/timezone information throw exceptions:

```php
// Date objects block time operations
$date->setTime(12, 0, 0);     // LogicException
$date->setTimezone($tz);      // LogicException
$date->format('H:i:s');       // InvalidArgumentException
$date->modify('+1 hour');     // LogicException

// Time objects block date operations
$time->setDate(2025, 1, 15);  // LogicException
$time->setTimezone($tz);      // LogicException
$time->format('Y-m-d');       // InvalidArgumentException
$time->modify('+1 day');      // LogicException
```

## PHPStan Integration

The package includes a PHPStan rule that detects comparison operators used on date/time objects. To enable it, add to your `phpstan.neon`:

```neon
includes:
    - vendor/janklan/simple-date-time/phpstan-extension.neon
```

This catches errors like:

```php
$date1 < $date2  // Error: Use isBefore() instead
$time1 == $time2 // Error: Use isSameTimeAs() instead
```

## Doctrine Integration

For database persistence, see [janklan/simple-date-time-doctrine](https://github.com/janklan/simple-date-time-doctrine).

## Contributing

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run static analysis
vendor/bin/phpstan

# Fix code style
vendor/bin/php-cs-fixer fix
```

## License

MIT
