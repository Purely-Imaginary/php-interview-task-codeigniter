<?php

namespace App\FleetManagement\Domain;

use App\Common\Domain\CoasterConfigurationChanged;
use App\Common\Domain\Distance;
use App\Common\Domain\TimeRange;

/**
 * Coaster Aggregate Root
 * Represents a roller coaster attraction
 */
class Coaster
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $personnelCount;

    /**
     * @var int
     */
    private $dailyClients;

    /**
     * @var Distance
     */
    private $trackLength;

    /**
     * @var TimeRange
     */
    private $operatingHours;

    /**
     * @var array
     */
    private $wagons = [];

    /**
     * @var array
     */
    private $domainEvents = [];

    /**
     * @param string $id
     * @param int $personnelCount
     * @param int $dailyClients
     * @param Distance $trackLength
     * @param TimeRange $operatingHours
     */
    public function __construct(
        $id,
        $personnelCount,
        $dailyClients,
        Distance $trackLength,
        TimeRange $operatingHours
    ) {
        $this->id = $id;
        $this->personnelCount = $personnelCount;
        $this->dailyClients = $dailyClients;
        $this->trackLength = $trackLength;
        $this->operatingHours = $operatingHours;
    }

    /**
     * Get the coaster ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the personnel count
     *
     * @return int
     */
    public function getPersonnelCount()
    {
        return $this->personnelCount;
    }

    /**
     * Get the daily clients
     *
     * @return int
     */
    public function getDailyClients()
    {
        return $this->dailyClients;
    }

    /**
     * Get the track length
     *
     * @return Distance
     */
    public function getTrackLength()
    {
        return $this->trackLength;
    }

    /**
     * Get the operating hours
     *
     * @return TimeRange
     */
    public function getOperatingHours()
    {
        return $this->operatingHours;
    }

    /**
     * Get the wagons
     *
     * @return array
     */
    public function getWagons()
    {
        return $this->wagons;
    }

    /**
     * Update coaster details
     *
     * @param int $personnelCount
     * @param int $dailyClients
     * @param TimeRange $operatingHours
     */
    public function update($personnelCount, $dailyClients, TimeRange $operatingHours)
    {
        $this->personnelCount = $personnelCount;
        $this->dailyClients = $dailyClients;
        $this->operatingHours = $operatingHours;

        $this->recordEvent(new CoasterConfigurationChanged($this->id));
    }

    /**
     * Add a wagon
     *
     * @param Wagon $wagon
     */
    public function addWagon(Wagon $wagon)
    {
        $this->wagons[$wagon->getId()] = $wagon;

        $this->recordEvent(new CoasterConfigurationChanged($this->id));
    }

    /**
     * Remove a wagon
     *
     * @param string $wagonId
     * @throws \InvalidArgumentException
     */
    public function removeWagon($wagonId)
    {
        if (!isset($this->wagons[$wagonId])) {
            throw new \InvalidArgumentException("Wagon with ID $wagonId not found");
        }

        unset($this->wagons[$wagonId]);

        $this->recordEvent(new CoasterConfigurationChanged($this->id));
    }

    /**
     * Record a domain event
     *
     * @param mixed $event
     */
    private function recordEvent($event)
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Get and clear domain events
     *
     * @return array
     */
    public function releaseEvents()
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        $wagonsArray = [];
        foreach ($this->wagons as $wagon) {
            $wagonsArray[$wagon->getId()] = $wagon->toArray();
        }

        return [
            'id' => $this->id,
            'personnel_count' => $this->personnelCount,
            'daily_clients' => $this->dailyClients,
            'track_length' => $this->trackLength->toArray(),
            'operating_hours' => $this->operatingHours->toArray(),
            'wagons' => $wagonsArray
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
        $coaster = new self(
            $data['id'],
            $data['personnel_count'],
            $data['daily_clients'],
            Distance::fromArray($data['track_length']),
            TimeRange::fromArray($data['operating_hours'])
        );

        if (isset($data['wagons']) && is_array($data['wagons'])) {
            foreach ($data['wagons'] as $wagonData) {
                $coaster->addWagon(Wagon::fromArray($wagonData));
            }
        }

        return $coaster;
    }

    /**
     * Create a new coaster with a generated ID
     *
     * @param int $personnelCount
     * @param int $dailyClients
     * @param int $trackLengthMeters
     * @param string $operatingHoursStart
     * @param string $operatingHoursEnd
     * @return self
     */
    public static function create(
        $personnelCount,
        $dailyClients,
        $trackLengthMeters,
        $operatingHoursStart,
        $operatingHoursEnd
    ) {
        $coaster = new self(
            uniqid('coaster_'),
            $personnelCount,
            $dailyClients,
            new Distance($trackLengthMeters),
            new TimeRange($operatingHoursStart, $operatingHoursEnd)
        );

        $coaster->recordEvent(new CoasterConfigurationChanged($coaster->getId()));

        return $coaster;
    }
}
