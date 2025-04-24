<?php

namespace Sididev\PrayerTimes;

class Method
{
    private $name;
    private $fajrAngle;
    private $ishaAngle;
    private $maghribAngle;
    private $asrFactor;

    /**
     * Méthodes de calcul prédéfinies
     */
    private static $methods = [
        'MWL' => [
            'fajrAngle' => 18,
            'ishaAngle' => 17,
            'maghribAngle' => 0,
            'asrFactor' => 1
        ],
        'ISNA' => [
            'fajrAngle' => 15,
            'ishaAngle' => 15,
            'maghribAngle' => 0,
            'asrFactor' => 1
        ],
        'EGYPT' => [
            'fajrAngle' => 19.5,
            'ishaAngle' => 17.5,
            'maghribAngle' => 0,
            'asrFactor' => 1
        ],
        'MAKKAH' => [
            'fajrAngle' => 18.5,
            'ishaAngle' => '90 min',
            'maghribAngle' => 0,
            'asrFactor' => 1
        ],
        'KARACHI' => [
            'fajrAngle' => 18,
            'ishaAngle' => 18,
            'maghribAngle' => 0,
            'asrFactor' => 1
        ],
        'TEHRAN' => [
            'fajrAngle' => 17.7,
            'ishaAngle' => 14,
            'maghribAngle' => 4.5,
            'asrFactor' => 1
        ],
        'JAFARI' => [
            'fajrAngle' => 16,
            'ishaAngle' => 14,
            'maghribAngle' => 4,
            'asrFactor' => 1
        ]
    ];

    /**
     * Constructeur
     *
     * @param string $name Nom de la méthode
     */
    public function __construct($name = 'CUSTOM')
    {
        $this->name = $name;


        $this->fajrAngle = 18;
        $this->ishaAngle = 17;
        $this->maghribAngle = 0;
        $this->asrFactor = 1;
    }

    /**
     * Obtenir une méthode prédéfinie
     *
     * @param string $name Nom de la méthode
     * @return Method Instance de la méthode
     */
    public static function getMethod($name)
    {
        if (!isset(self::$methods[$name])) {
            throw new \InvalidArgumentException("Méthode de calcul inconnue: $name");
        }

        $method = new self($name);
        $params = self::$methods[$name];

        $method->setFajrAngle($params['fajrAngle']);
        $method->setIshaAngle($params['ishaAngle']);
        $method->setMaghribAngle($params['maghribAngle']);
        $method->setAsrFactor($params['asrFactor']);

        return $method;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFajrAngle()
    {
        return $this->fajrAngle;
    }

    public function setFajrAngle($angle)
    {
        $this->fajrAngle = $angle;
        return $this;
    }

    public function getIshaAngle()
    {
        return $this->ishaAngle;
    }

    public function setIshaAngle($angle)
    {
        $this->ishaAngle = $angle;
        return $this;
    }

    public function getMaghribAngle()
    {
        return $this->maghribAngle;
    }

    public function setMaghribAngle($angle)
    {
        $this->maghribAngle = $angle;
        return $this;
    }

    public function getAsrFactor()
    {
        return $this->asrFactor;
    }

    public function setAsrFactor($factor)
    {
        $this->asrFactor = $factor;
        return $this;
    }
}
