<?php

namespace App\Common\Domain;

/**
 * Capacity Value Object
 * Represents a capacity in number of seats
 */
class Capacity
{
    /**
     * @var int Number of seats
     */
    private $seats;

    /**
     * @param int $seats Number of seats
     */
    public function __construct($seats)
    {
        $this->validateCapacity($seats);
        $this->seats = $seats;
    }

    /**
     * Get the number of seats
     *
     * @return int
     */
    public function getSeats()
    {
        return $this->seats;
    }

    /**
     * Validate capacity
     *
     * @param int $seats
     * @throws \InvalidArgumentException
     */
    private function validateCapacity($seats)
    {
        if (!is_numeric($seats) || $seats <= 0 || floor($seats) != $seats) {
            throw new \InvalidArgumentException("Capacity must be a positive integer");
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
            'seats' => $this->seats
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
        return new self($data['seats']);
    }
}
