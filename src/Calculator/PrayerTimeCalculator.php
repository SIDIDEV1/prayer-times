<?php

namespace Sididev\PrayerTimes\Calculator;

use DateTime;
use DateTimeZone;
use Sididev\PrayerTimes\Method;

class PrayerTimeCalculator
{
    private $sunCalculator;

    public function __construct()
    {
        $this->sunCalculator = new SunPositionCalculator();
    }

    /**
     * Calcule les horaires de prière pour une date et un lieu donnés
     *
     * @param DateTime $date Date pour le calcul
     * @param float $latitude Latitude en degrés
     * @param float $longitude Longitude en degrés
     * @param Method $method Méthode de calcul à utiliser
     * @param int $elevation Élévation en mètres (optionnel)
     * @return array Horaires de prière calculés
     */
    public function calculate(DateTime $date, $latitude, $longitude, Method $method, $elevation = 0)
    {

        $jd = $this->sunCalculator->dateToJulianDay($date);


        $sun = $this->sunCalculator->calculate($jd, $latitude, $longitude);


        if ($elevation > 0) {

            $elevationAdjustment = 0.0347 * sqrt($elevation);
        } else {
            $elevationAdjustment = 0;
        }


        $times = [];


        $fajrAngle = $method->getFajrAngle() + $elevationAdjustment;
        $fajrHA = $this->sunCalculator->calculateHourAngle($latitude, $sun['declination'], -$fajrAngle);
        $times['Fajr'] = $sun['transit'] - $fajrHA / 15;


        $times['Sunrise'] = $sun['sunrise'];


        $times['Dhuhr'] = $sun['transit'];


        $times['Asr'] = $this->calculateAsrTime($sun, $method->getAsrFactor(), $latitude);


        $times['Sunset'] = $sun['sunset'];


        $maghribValue = $method->getMaghribAngle();
        if (is_numeric($maghribValue) && $maghribValue > 0) {

            $maghribHA = $this->sunCalculator->calculateHourAngle($latitude, $sun['declination'], -$maghribValue);
            $times['Maghrib'] = $sun['transit'] + $maghribHA / 15;
        } else {

            $times['Maghrib'] = $sun['sunset'];
        }


        $ishaValue = $method->getIshaAngle();
        if (is_string($ishaValue) && preg_match('/(\d+)\s*min/', $ishaValue, $matches)) {

            $minutes = (int)$matches[1];
            $times['Isha'] = $times['Maghrib'] + $minutes / 60;
        } else {

            $ishaAngle = (float)$ishaValue + $elevationAdjustment;
            $ishaHA = $this->sunCalculator->calculateHourAngle($latitude, $sun['declination'], -$ishaAngle);
            $times['Isha'] = $sun['transit'] + $ishaHA / 15;
        }


        $times['Midnight'] = $this->calculateMidnight($times['Sunset'], $times['Fajr']);


        $times['Imsak'] = $times['Fajr'] - 10/60;


        foreach ($times as $name => $time) {
            $times[$name] = $this->normalizeHours($time);
        }

        return $times;
    }

    /**
     * Calcule l'heure de Asr en fonction du facteur d'ombre
     */
    private function calculateAsrTime($sun, $asrFactor, $latitude)
    {

        $sunDeclRad = $sun['declination'];
        $latRad = deg2rad($latitude);


        $tanAltitude = 1 / ($asrFactor + tan(abs($latRad - $sunDeclRad)));
        $asrAlt = rad2deg(atan($tanAltitude));


        $asrHA = $this->sunCalculator->calculateHourAngle($latitude, $sunDeclRad, $asrAlt);


        return $sun['transit'] + $asrHA / 15;
    }

    /**
     * Calcule l'heure du minuit islamique (à mi-chemin entre le coucher et l'aube)
     */
    private function calculateMidnight($sunset, $fajr)
    {
        if ($fajr > $sunset) {

            return ($sunset + $fajr) / 2;
        } else {

            return ($sunset + $fajr + 24) / 2 % 24;
        }
    }

    /**
     * Normalise les heures pour qu'elles soient dans l'intervalle 0-24
     */
    private function normalizeHours($hours): int
    {
        $h = $hours % 24;
        return ($h < 0) ? $h + 24 : $h;
    }
}
