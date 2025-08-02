<?php

namespace App\Common\Domain;

/**
 * Distance Value Object
 * Represents a distance in meters
 */
class Distance
{
    /**
     * @var int Distance in meters
     */
    private $meters;

    /**
     * @param int $meters Distance in meters
     */
    public function __construct($meters)
    {
        $this->validateDistance($meters);
        $this->meters = $meters;
    }

    /**
     * Get the distance in meters
     *
     * @return int
     */
    public function getMeters()
    {
        return $this->meters;
    }

    /**
     * Get the distance in kilometers
     *
     * @return float
     */
    public function getKilometers()
    {
        return $this->meters / 1000;
    }

    /**
     * Validate distance
     *
     * @param int $meters
     * @throws \InvalidArgumentException
     */
    private function validateDistance($meters)
    {
        if (!is_numeric($meters) || $meters <= 0) {
            throw new \InvalidArgumentException("Distance must be a positive number");
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
            'meters' => $this->meters
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
        return new self($data['meters']);
    }
}
