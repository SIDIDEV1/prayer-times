<?php

namespace Sididev\PrayerTimes;

use DateTime;
use DateTimeZone;
use Sididev\PrayerTimes\Calculator\PrayerTimeCalculator;
use Sididev\PrayerTimes\Exceptions\PrayerTimesException;

class PrayerTimes
{

    const METHOD_ISNA = 'ISNA';
    const METHOD_MWL = 'MWL';
    const METHOD_EGYPT = 'EGYPT';
    const METHOD_MAKKAH = 'MAKKAH';
    const METHOD_KARACHI = 'KARACHI';
    const METHOD_TEHRAN = 'TEHRAN';
    const METHOD_JAFARI = 'JAFARI';
    const METHOD_CUSTOM = 'CUSTOM';


    const TIME_FORMAT_24H = '24h';
    const TIME_FORMAT_12H = '12h';
    const TIME_FORMAT_FLOAT = 'float';
    const TIME_FORMAT_ISO8601 = 'iso8601';


    const MIDNIGHT_STANDARD = 'standard';
    const MIDNIGHT_JAFARI = 'jafari';


    private $method;
    private $calculator;
    private $customMethod = null;
    private $adjustments = [];
    private $midnightMode;
    private $elevation = 0;

    /**
     * Constructeur
     *
     * @param string $method Méthode de calcul à utiliser
     */
    public function __construct($method = self::METHOD_MWL)
    {
        $this->method = $method;
        $this->calculator = new PrayerTimeCalculator();
        $this->midnightMode = self::MIDNIGHT_STANDARD;


        $this->adjustments = [
            'Imsak' => 0,
            'Fajr' => 0,
            'Sunrise' => 0,
            'Dhuhr' => 0,
            'Asr' => 0,
            'Sunset' => 0,
            'Maghrib' => 0,
            'Isha' => 0,
            'Midnight' => 0
        ];
    }

    /**
     * Récupérer les horaires de prière pour aujourd'hui
     *
     * @param float $latitude Latitude en degrés
     * @param float $longitude Longitude en degrés
     * @param string $timezone Fuseau horaire (identifiant ou offset)
     * @param float $elevation Élévation en mètres (optionnel)
     * @param string $format Format de sortie (24h, 12h, float, iso8601)
     * @return array Horaires de prière
     */
    public function getTimesForToday($latitude, $longitude, $timezone, $elevation = null, $format = self::TIME_FORMAT_24H)
    {
        $date = new DateTime('now', new DateTimeZone($this->normalizeTimezone($timezone)));

        if ($elevation !== null) {
            $this->elevation = $elevation;
        }

        return $this->getTimes($date, $latitude, $longitude, $format);
    }

    /**
     * Récupérer les horaires de prière pour une date spécifique
     *
     * @param DateTime $date Date pour laquelle calculer les horaires
     * @param float $latitude Latitude en degrés
     * @param float $longitude Longitude en degrés
     * @param string $format Format de sortie (24h, 12h, float, iso8601)
     * @return array Horaires de prière
     */
    public function getTimes(DateTime $date, $latitude, $longitude, $format = self::TIME_FORMAT_24H)
    {

        $this->validateCoordinates($latitude, $longitude);


        $methodConfig = $this->getMethodConfig();


        $times = $this->calculator->calculate($date, $latitude, $longitude, $methodConfig, $this->elevation);


        if ($this->midnightMode === self::MIDNIGHT_JAFARI) {
            $times['Midnight'] = ($times['Sunset'] + $times['Sunrise'] + 24) / 2 % 24;
        }


        $times = $this->applyAdjustments($times);


        return $this->formatTimes($times, $format);
    }

    /**
     * Définir une méthode personnalisée
     *
     * @param Method $method Méthode personnalisée
     * @return $this
     */
    public function setCustomMethod(Method $method)
    {
        $this->customMethod = $method;
        $this->method = self::METHOD_CUSTOM;
        return $this;
    }

    /**
     * Définir le mode de calcul du minuit
     *
     * @param string $mode Mode de calcul du minuit
     * @return $this
     */
    public function setMidnightMode($mode)
    {
        if ($mode !== self::MIDNIGHT_STANDARD && $mode !== self::MIDNIGHT_JAFARI) {
            throw new PrayerTimesException("Mode de calcul du minuit invalide: $mode");
        }
        $this->midnightMode = $mode;
        return $this;
    }

    /**
     * Définir l'élévation du lieu
     *
     * @param float $elevation Élévation en mètres
     * @return $this
     */
    public function setElevation($elevation)
    {
        $this->elevation = max(0, $elevation);
        return $this;
    }

    /**
     * Ajuster les horaires de prière (en minutes)
     *
     * @param int $imsak Ajustement pour Imsak (en minutes)
     * @param int $fajr Ajustement pour Fajr (en minutes)
     * @param int $sunrise Ajustement pour le lever du soleil (en minutes)
     * @param int $dhuhr Ajustement pour Dhuhr (en minutes)
     * @param int $asr Ajustement pour Asr (en minutes)
     * @param int $sunset Ajustement pour le coucher du soleil (en minutes)
     * @param int $maghrib Ajustement pour Maghrib (en minutes)
     * @param int $isha Ajustement pour Isha (en minutes)
     * @param int $midnight Ajustement pour minuit (en minutes)
     * @return $this
     */
    public function tune($imsak = 0, $fajr = 0, $sunrise = 0, $dhuhr = 0, $asr = 0, $sunset = 0, $maghrib = 0, $isha = 0, $midnight = 0)
    {
        $this->adjustments = [
            'Imsak' => $imsak,
            'Fajr' => $fajr,
            'Sunrise' => $sunrise,
            'Dhuhr' => $dhuhr,
            'Asr' => $asr,
            'Sunset' => $sunset,
            'Maghrib' => $maghrib,
            'Isha' => $isha,
            'Midnight' => $midnight
        ];
        return $this;
    }

    /**
     * Obtenir la prière actuelle et la prochaine prière
     *
     * @param array $times Horaires de prière au format float
     * @param DateTime|null $currentTime Heure actuelle (null pour utiliser l'heure système)
     * @return array Information sur la prière actuelle et la suivante
     */
    public function getCurrentPrayer($times, DateTime $currentTime = null)
    {

        if (isset($times['Fajr']) && !is_float($times['Fajr'])) {
            $times = $this->formatTimes($times, self::TIME_FORMAT_FLOAT);
        }

        if ($currentTime === null) {
            $currentTime = new DateTime();
        }

        $currentHour = (float)$currentTime->format('G') +
            (float)$currentTime->format('i') / 60 +
            (float)$currentTime->format('s') / 3600;


        $prayers = [
            'Fajr' => $times['Fajr'],
            'Sunrise' => $times['Sunrise'],
            'Dhuhr' => $times['Dhuhr'],
            'Asr' => $times['Asr'],
            'Maghrib' => $times['Maghrib'],
            'Isha' => $times['Isha']
        ];


        $current = '';
        $next = '';
        $nextTime = 0;
        $timeDiff = 0;

        if ($currentHour < $times['Fajr']) {
            $current = 'Isha';
            $next = 'Fajr';
            $nextTime = $times['Fajr'];
            $timeDiff = $nextTime - $currentHour;
        }
        elseif ($currentHour < $times['Sunrise']) {
            $current = 'Fajr';
            $next = 'Sunrise';
            $nextTime = $times['Sunrise'];
            $timeDiff = $nextTime - $currentHour;
        }
        elseif ($currentHour < $times['Dhuhr']) {
            $current = 'Sunrise';
            $next = 'Dhuhr';
            $nextTime = $times['Dhuhr'];
            $timeDiff = $nextTime - $currentHour;
        }
        elseif ($currentHour < $times['Asr']) {
            $current = 'Dhuhr';
            $next = 'Asr';
            $nextTime = $times['Asr'];
            $timeDiff = $nextTime - $currentHour;
        }
        elseif ($currentHour < $times['Maghrib']) {
            $current = 'Asr';
            $next = 'Maghrib';
            $nextTime = $times['Maghrib'];
            $timeDiff = $nextTime - $currentHour;
        }
        elseif ($currentHour < $times['Isha']) {
            $current = 'Maghrib';
            $next = 'Isha';
            $nextTime = $times['Isha'];
            $timeDiff = $nextTime - $currentHour;
        }
        else {
            $current = 'Isha';
            $next = 'Fajr';
            $nextTime = $times['Fajr'] + 24;
            $timeDiff = $nextTime - $currentHour;
        }


        $remainingMinutes = round($timeDiff * 60);


        $hours = floor($remainingMinutes / 60);
        $minutes = $remainingMinutes % 60;
        $timeRemaining = sprintf('%02d:%02d', $hours, $minutes);

        return [
            'current' => $current,
            'next' => $next,
            'time_remaining' => $timeRemaining,
            'minutes_remaining' => $remainingMinutes
        ];
    }

    /**
     * Convertir un fuseau horaire numérique en identifiant valide
     *
     * @param mixed $timezone Fuseau horaire (numérique ou identifiant)
     * @return string Identifiant de fuseau horaire valide
     */
    private function normalizeTimezone($timezone)
    {
        if (is_numeric($timezone)) {
            $offset = (int)$timezone;


            $timezoneMap = [
                0 => 'UTC',
                1 => 'Europe/London',
                2 => 'Europe/Paris',
                3 => 'Europe/Moscow',
                4 => 'Asia/Dubai',
                5 => 'Asia/Karachi',
                6 => 'Asia/Dhaka',
                7 => 'Asia/Jakarta',
                8 => 'Asia/Shanghai',
                9 => 'Asia/Tokyo',
                10 => 'Australia/Sydney',
                11 => 'Pacific/Noumea',
                12 => 'Pacific/Auckland',
                -1 => 'Atlantic/Azores',
                -2 => 'Atlantic/South_Georgia',
                -3 => 'America/Sao_Paulo',
                -4 => 'America/New_York',
                -5 => 'America/Chicago',
                -6 => 'America/Denver',
                -7 => 'America/Los_Angeles',
                -8 => 'Pacific/Pitcairn',
                -9 => 'America/Anchorage',
                -10 => 'Pacific/Honolulu',
                -11 => 'Pacific/Niue',
                -12 => 'Pacific/Wake'
            ];

            return $timezoneMap[$offset] ?? 'UTC';
        }

        return $timezone;
    }

    /**
     * Valider les coordonnées géographiques
     *
     * @param float $latitude Latitude en degrés
     * @param float $longitude Longitude en degrés
     * @throws PrayerTimesException Si les coordonnées sont invalides
     */
    private function validateCoordinates($latitude, $longitude)
    {
        if (!is_numeric($latitude) || $latitude < -90 || $latitude > 90) {
            throw new PrayerTimesException("Latitude invalide: $latitude");
        }

        if (!is_numeric($longitude) || $longitude < -180 || $longitude > 180) {
            throw new PrayerTimesException("Longitude invalide: $longitude");
        }
    }

    /**
     * Obtenir la configuration pour la méthode choisie
     *
     * @return Method Configuration de la méthode
     */
    private function getMethodConfig()
    {
        if ($this->method === self::METHOD_CUSTOM && $this->customMethod !== null) {
            return $this->customMethod;
        }

        return Method::getMethod($this->method);
    }

    /**
     * Appliquer les ajustements aux horaires calculés
     *
     * @param array $times Horaires calculés
     * @return array Horaires ajustés
     */
    private function applyAdjustments($times)
    {
        foreach ($this->adjustments as $prayer => $adjustment) {
            if (isset($times[$prayer]) && $adjustment != 0) {
                $times[$prayer] += $adjustment / 60;
            }
        }

        return $times;
    }

    /**
     * Formater les horaires selon le format demandé
     *
     * @param array $times Horaires en format décimal
     * @param string $format Format de sortie souhaité
     * @return array Horaires formatés
     */
    private function formatTimes($times, $format)
    {
        $result = [];

        foreach ($times as $prayer => $time) {
            switch ($format) {
                case self::TIME_FORMAT_12H:
                    $result[$prayer] = $this->convertTo12HourFormat($time);
                    break;
                case self::TIME_FORMAT_FLOAT:
                    $result[$prayer] = $time;
                    break;
                case self::TIME_FORMAT_ISO8601:
                    $result[$prayer] = $this->convertToISO8601Format($time);
                    break;
                case self::TIME_FORMAT_24H:
                default:
                    $result[$prayer] = $this->convertTo24HourFormat($time);
                    break;
            }
        }

        return $result;
    }

    /**
     * Convertir un temps décimal en format 24h (hh:mm)
     *
     * @param float $time Temps en format décimal
     * @return string Temps au format 24h
     */
    private function convertTo24HourFormat($time)
    {
        $hours = (int)$time;
        $minutes = (int)(($time - $hours) * 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Convertir un temps décimal en format 12h (h:mm AM/PM)
     *
     * @param float $time Temps en format décimal
     * @return string Temps au format 12h
     */
    private function convertTo12HourFormat($time)
    {
        $hours = (int)$time;
        $minutes = (int)(($time - $hours) * 60);

        $suffix = $hours >= 12 ? 'PM' : 'AM';
        $hours = $hours % 12;
        $hours = $hours ? $hours : 12;

        return sprintf('%d:%02d %s', $hours, $minutes, $suffix);
    }

    /**
     * Convertir un temps décimal en format ISO8601
     *
     * @param float $time Temps en format décimal
     * @return string Temps au format ISO8601
     */
    private function convertToISO8601Format($time)
    {
        $hours = (int)$time;
        $minutes = (int)(($time - $hours) * 60);
        $seconds = (int)((($time - $hours) * 60 - $minutes) * 60);

        return sprintf('T%02d:%02d:%02dZ', $hours, $minutes, $seconds);
    }
}
