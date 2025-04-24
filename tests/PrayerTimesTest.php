<?php

namespace Sididev\PrayerTimes\Tests;

use PHPUnit\Framework\TestCase;
use Sididev\PrayerTimes\PrayerTimes;
use DateTime;
use DateTimeZone;

class PrayerTimesTest extends TestCase
{
    public function testBasicCalculation()
    {
        $prayerTimes = new PrayerTimes();
        $times = $prayerTimes->getTimesForToday(33.5733, -7.6454, 'Africa/Casablanca');

        $this->assertIsArray($times);
        $this->assertArrayHasKey('Fajr', $times);
        $this->assertArrayHasKey('Sunrise', $times);
        $this->assertArrayHasKey('Dhuhr', $times);
        $this->assertArrayHasKey('Asr', $times);
        $this->assertArrayHasKey('Maghrib', $times);
        $this->assertArrayHasKey('Isha', $times);
    }

    public function testTimeFormat()
    {
        $prayerTimes = new PrayerTimes();
        $times = $prayerTimes->getTimesForToday(33.5733, -7.6454, 'Africa/Casablanca');

        foreach ($times as $prayer => $time) {
            $this->assertMatchesRegularExpression('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $time, "L'horaire de $prayer n'est pas au format correct");
        }
    }

    public function testDifferentMethods()
    {
        $methods = [
            PrayerTimes::METHOD_MWL,
            PrayerTimes::METHOD_ISNA,
            PrayerTimes::METHOD_EGYPT
        ];

        foreach ($methods as $method) {
            $prayerTimes = new PrayerTimes($method);
            $times = $prayerTimes->getTimesForToday(33.5733, -7.6454, 'Africa/Casablanca');

            $this->assertIsArray($times);
            $this->assertArrayHasKey('Fajr', $times);
        }
    }

    public function testCustomMethod()
    {
        $prayerTimes = new PrayerTimes(PrayerTimes::METHOD_CUSTOM);
        $customMethod = new \Sididev\PrayerTimes\Method('Custom');
        $customMethod->setFajrAngle(18)
            ->setIshaAngle(17)
            ->setMaghribAngle(0)
            ->setAsrFactor(1);

        $prayerTimes->setCustomMethod($customMethod);

        $times = $prayerTimes->getTimesForToday(33.5733, -7.6454, 'Africa/Casablanca');

        $this->assertIsArray($times);
        $this->assertArrayHasKey('Fajr', $times);
    }

    public function testSpecificDate()
    {
        $prayerTimes = new PrayerTimes();
        $date = new DateTime('2025-01-01', new DateTimeZone('UTC'));

        $times = $prayerTimes->getTimes($date, 33.5733, -7.6454, 'Africa/Casablanca');

        $this->assertIsArray($times);
        $this->assertArrayHasKey('Fajr', $times);
    }
}
