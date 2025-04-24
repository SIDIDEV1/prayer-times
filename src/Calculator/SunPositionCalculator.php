<?php

namespace Sididev\PrayerTimes\Calculator;

class SunPositionCalculator
{
    /**
     * Calcule la position du soleil pour une date et une localisation données
     *
     * @param int $jd Jour julien
     * @param float $latitude Latitude en degrés
     * @param float $longitude Longitude en degrés
     * @return array Position du soleil et autres paramètres astronomiques
     */
    public function calculate($jd, $latitude, $longitude)
    {

        $lat = deg2rad($latitude);
        $lng = deg2rad($longitude);


        $D = $jd - 2451545.0;
        $T = $D / 36525.0;


        $eps = deg2rad(23.43929111 - (46.8150 * $T + 0.00059 * $T * $T - 0.001813 * $T * $T * $T) / 3600.0);


        $M = deg2rad(357.52910 + 35999.05030 * $T - 0.0001559 * $T * $T - 0.00000048 * $T * $T * $T);
        $L0 = deg2rad(280.46645 + 36000.76983 * $T + 0.0003032 * $T * $T);


        $C = deg2rad((1.914600 - 0.004817 * $T - 0.000014 * $T * $T) * sin($M) +
            (0.019993 - 0.000101 * $T) * sin(2 * $M) +
            0.000290 * sin(3 * $M));


        $L = $L0 + $C;


        $v = $M + $C;


        $R = 1.000001018 * (1 - 0.016708634 * cos($M) - 0.000139589 * cos(2 * $M));


        $X = cos($L);
        $Y = cos($eps) * sin($L);
        $Z = sin($eps) * sin($L);


        $ra = atan2($Y, $X);
        $dec = asin($Z);


        if ($ra < 0) $ra += 2 * M_PI;


        $GMST0 = deg2rad(280.46061837 + 360.98564736629 * $D + 0.000387933 * $T * $T - $T * $T * $T / 38710000.0);


        $h = $GMST0 + $lng - $ra;


        $h0 = $GMST0 + $lng + M_PI - $ra;


        $transit = 12.0 - rad2deg($h0) / 15.0;



        $h_rise_set = acos((sin(deg2rad(-0.83)) - sin($lat) * sin($dec)) / (cos($lat) * cos($dec)));


        $sunrise = fmod($transit - rad2deg($h_rise_set) / 15.0, 24);
        if ($sunrise < 0) $sunrise += 24;

        $sunset = fmod($transit + rad2deg($h_rise_set) / 15.0, 24);
        if ($sunset < 0) $sunset += 24;


        $h_fajr = acos((sin(deg2rad(-18)) - sin($lat) * sin($dec)) / (cos($lat) * cos($dec)));
        $h_isha = $h_fajr;


        $fajr_time = fmod($transit - rad2deg($h_fajr) / 15.0, 24);
        if ($fajr_time < 0) $fajr_time += 24;

        $isha_time = fmod($transit + rad2deg($h_isha) / 15.0, 24);
        if ($isha_time < 0) $isha_time += 24;


        return [
            'declination' => $dec,
            'equation_of_time' => rad2deg($ra - $L0) * 4,
            'transit' => $transit,
            'sunrise' => $sunrise,
            'sunset' => $sunset,
            'fajr_angle' => $fajr_time,
            'isha_angle' => $isha_time,
            'hour_angle' => $h,
            'sun_altitude' => asin(sin($lat) * sin($dec) + cos($lat) * cos($dec) * cos($h)),
            'sun_azimuth' => atan2(-sin($h), cos($lat) * tan($dec) - sin($lat) * cos($h))
        ];
    }

    /**
     * Convertit une date en jour julien
     *
     * @param DateTime $date Date à convertir
     * @return float Jour julien
     */
    public function dateToJulianDay($date)
    {
        $year = (int)$date->format('Y');
        $month = (int)$date->format('m');
        $day = (int)$date->format('d');
        $hour = (int)$date->format('G');
        $minute = (int)$date->format('i');
        $second = (int)$date->format('s');


        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }


        $A = floor($year / 100);
        $B = 2 - $A + floor($A / 4);


        $JD = floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $B - 1524.5;


        $JD += ($hour + $minute / 60.0 + $second / 3600.0) / 24.0;

        return $JD;
    }

    /**
     * Calcule l'angle horaire pour un angle d'altitude donné
     *
     * @param float $latitude Latitude en degrés
     * @param float $declination Déclinaison solaire en radians
     * @param float $altitude Altitude angulaire en degrés
     * @return float Angle horaire en degrés
     */
    public function calculateHourAngle($latitude, $declination, $altitude)
    {
        $lat_rad = deg2rad($latitude);
        $alt_rad = deg2rad($altitude);

        $cos_ha = (sin($alt_rad) - sin($lat_rad) * sin($declination)) /
            (cos($lat_rad) * cos($declination));


        if ($cos_ha > 1) return 0;
        if ($cos_ha < -1) return 180;

        return rad2deg(acos($cos_ha));
    }
}
