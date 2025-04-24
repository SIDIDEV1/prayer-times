# Prayer Times Library

A modern PHP library for calculating Islamic prayer times based on location, date, and various calculation methods.

## Installation

Install the library with Composer:

```bash
composer require sididev/prayer-times
```

## Usage

### Basic Example

```php
<?php
require 'vendor/autoload.php';

use Sididev\PrayerTimes\PrayerTimes;

// Create an instance with the Muslim World League method
$prayerTimes = new PrayerTimes(PrayerTimes::METHOD_MWL);

// Get prayer times for today
$latitude = 33.5733;  // Casablanca, Morocco
$longitude = -7.6454;
$timezone = 'Africa/Casablanca';

$times = $prayerTimes->getTimesForToday($latitude, $longitude, $timezone);

// Display the results
foreach ($times as $prayer => $time) {
    echo "$prayer: $time\n";
}
```

### Get Prayer Times for a Specific Date

```php
use Sididev\PrayerTimes\PrayerTimes;
use DateTime;
use DateTimeZone;

$prayerTimes = new PrayerTimes();
$date = new DateTime('2025-01-01', new DateTimeZone('UTC'));

$times = $prayerTimes->getTimes($date, 33.5733, -7.6454, 'Africa/Casablanca');
```

### Different Time Formats

```php
// 24-hour format (default): "14:30"
$times24h = $prayerTimes->getTimesForToday($latitude, $longitude, $timezone, null, PrayerTimes::TIME_FORMAT_24H);

// 12-hour format: "2:30 PM"
$times12h = $prayerTimes->getTimesForToday($latitude, $longitude, $timezone, null, PrayerTimes::TIME_FORMAT_12H);

// Float format: 14.5
$timesFloat = $prayerTimes->getTimesForToday($latitude, $longitude, $timezone, null, PrayerTimes::TIME_FORMAT_FLOAT);

// ISO8601 format: "T14:30:00Z"
$timesISO = $prayerTimes->getTimesForToday($latitude, $longitude, $timezone, null, PrayerTimes::TIME_FORMAT_ISO8601);
```

### Custom Calculation Methods

```php
use Sididev\PrayerTimes\PrayerTimes;
use Sididev\PrayerTimes\Method;

// Create a custom calculation method
$customMethod = new Method('Custom');
$customMethod->setFajrAngle(18)
             ->setIshaAngle(17)
             ->setMaghribAngle(0)
             ->setAsrFactor(1);

// Use the custom method
$prayerTimes = new PrayerTimes(PrayerTimes::METHOD_CUSTOM);
$prayerTimes->setCustomMethod($customMethod);

$times = $prayerTimes->getTimesForToday($latitude, $longitude, $timezone);
```

### Fine-tuning Prayer Times

You can adjust the prayer times (in minutes) to match the official times in your region:

```php
$prayerTimes = new PrayerTimes();
$prayerTimes->tune(0, 2, 0, 1, 0, 0, 3, 2);
// Arguments: Imsak, Fajr, Sunrise, Dhuhr, Asr, Sunset, Maghrib, Isha, Midnight
```

### Get Current Prayer Information

```php
$prayerTimes = new PrayerTimes();
$times = $prayerTimes->getTimesForToday($latitude, $longitude, $timezone);

$currentPrayer = $prayerTimes->getCurrentPrayer($times);
echo "Current prayer: " . $currentPrayer['current'] . "\n";
echo "Next prayer: " . $currentPrayer['next'] . "\n";
echo "Time remaining: " . $currentPrayer['time_remaining'] . "\n";
```

### Account for Elevation

```php
$prayerTimes = new PrayerTimes();
$prayerTimes->setElevation(350); // in meters
$times = $prayerTimes->getTimesForToday($latitude, $longitude, $timezone);
```

## Available Calculation Methods

This library provides several predefined calculation methods:

| Constant | Description |
|-----------|-------------|
| `METHOD_MWL` | Muslim World League |
| `METHOD_ISNA` | Islamic Society of North America |
| `METHOD_EGYPT` | Egyptian General Authority of Survey |
| `METHOD_MAKKAH` | Umm al-Qura University, Makkah |
| `METHOD_KARACHI` | University of Islamic Sciences, Karachi |
| `METHOD_TEHRAN` | Institute of Geophysics, University of Tehran |
| `METHOD_JAFARI` | Shia Ithna Ashari |
| `METHOD_CUSTOM` | Custom parameters |

## Midnight Calculation Modes

```php
// Standard (midpoint between sunset and dawn)
$prayerTimes->setMidnightMode(PrayerTimes::MIDNIGHT_STANDARD);

// Jafari (midpoint between sunset and sunrise)
$prayerTimes->setMidnightMode(PrayerTimes::MIDNIGHT_JAFARI);
```

## Timezone Handling

The library accepts two formats for the `timezone` parameter:

1. **Timezone identifier**: `'Europe/Paris'`, `'Africa/Casablanca'`, etc.
2. **Numeric offset**: `1` (UTC+1), `2` (UTC+2), `-5` (UTC-5), etc.

## How Calculations Are Performed

Prayer times are calculated according to the following steps:

1. **Astronomical calculation** of the sun's position for the given date and coordinates
2. **Application of angles** according to the chosen calculation method
3. **Adjustments** for elevation, local preferences, etc.
4. **Formatting** of results according to the desired format

## Calculated Prayer Times

| Prayer | Description |
|--------|-------------|
| Imsak | Beginning of fasting (10 minutes before Fajr by default) |
| Fajr | Dawn prayer |
| Sunrise | Sunrise time |
| Dhuhr | Noon prayer |
| Asr | Afternoon prayer |
| Sunset | Sunset time |
| Maghrib | Sunset prayer |
| Isha | Night prayer |
| Midnight | Islamic midnight |

## Contributing

Contributions are welcome! Feel free to open an issue or submit a pull request.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
