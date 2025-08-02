<?php

namespace App\Common\Domain;

/**
 * Speed Value Object
 * Represents a speed in meters per second
 */
class Speed
{
    /**
     * @var float Speed in meters per second
     */
    private $metersPerSecond;

    /**
     * @param float $metersPerSecond Speed in meters per second
     */
    public function __construct($metersPerSecond)
    {
        $this->validateSpeed($metersPerSecond);
        $this->metersPerSecond = $metersPerSecond;
    }

    /**
     * Get the speed in meters per second
     *
     * @return float
     */
    public function getMetersPerSecond()
    {
        return $this->metersPerSecond;
    }

    /**
     * Get the speed in kilometers per hour
     *
     * @return float
     */
    public function getKilometersPerHour()
    {
        return $this->metersPerSecond * 3.6;
    }

    /**
     * Calculate time to travel a distance
     *
     * @param Distance $distance
     * @return int Time in seconds
     */
    public function calculateTimeToTravel($distance)
    {
        return ceil($distance->getMeters() / $this->metersPerSecond);
    }

    /**
     * Validate speed
     *
     * @param float $metersPerSecond
     * @throws \InvalidArgumentException
     */
    private function validateSpeed($metersPerSecond)
    {
        if (!is_numeric($metersPerSecond) || $metersPerSecond <= 0) {
            throw new \InvalidArgumentException("Speed must be a positive number");
        }
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'meters_per_second' => $this->metersPerSecond
        ];
    }

    /**
     * Create from array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray($data)
    {
        return new self($data['meters_per_second']);
    }
}
