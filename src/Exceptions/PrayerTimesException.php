<?php

namespace Sididev\PrayerTimes\Exceptions;

/**
 * Exception specific to prayer times calculation errors
 */
class PrayerTimesException extends \Exception
{
    /**
     * Exception constructor
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
