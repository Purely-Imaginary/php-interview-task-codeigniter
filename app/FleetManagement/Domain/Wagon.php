<?php

namespace App\FleetManagement\Domain;

use App\Common\Domain\Capacity;
use App\Common\Domain\Speed;

/**
 * Wagon Entity
 * Represents a wagon assigned to a coaster
 */
class Wagon
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Capacity
     */
    private $seatCount;

    /**
     * @var Speed
     */
    private $speed;

    /**
     * @param string $id
     * @param Capacity $seatCount
     * @param Speed $speed
     */
    public function __construct($id, Capacity $seatCount, Speed $speed)
    {
        $this->id = $id;
        $this->seatCount = $seatCount;
        $this->speed = $speed;
    }

    /**
     * Get the wagon ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the seat count
     *
     * @return Capacity
     */
    public function getSeatCount()
    {
        return $this->seatCount;
    }

    /**
     * Get the speed
     *
     * @return Speed
     */
    public function getSpeed()
    {
        return $this->speed;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'seat_count' => $this->seatCount->toArray(),
            'speed' => $this->speed->toArray()
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
        return new self(
            $data['id'],
            Capacity::fromArray($data['seat_count']),
            Speed::fromArray($data['speed'])
        );
    }

    /**
     * Create a new wagon with a generated ID
     *
     * @param int $seatCount
     * @param float $speedMps
     * @return self
     */
    public static function create($seatCount, $speedMps)
    {
        return new self(
            uniqid('wagon_'),
            new Capacity($seatCount),
            new Speed($speedMps)
        );
    }
}
