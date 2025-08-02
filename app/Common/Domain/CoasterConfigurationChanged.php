<?php

namespace App\Common\Domain;

/**
 * Coaster Configuration Changed Event
 * Triggered when a coaster's configuration is changed
 */
class CoasterConfigurationChanged implements DomainEvent
{
    /**
     * @var string
     */
    private $coasterId;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @param string $coasterId
     */
    public function __construct($coasterId)
    {
        $this->coasterId = $coasterId;
        $this->timestamp = time();
    }

    /**
     * Get the event name
     *
     * @return string
     */
    public function getEventName()
    {
        return 'coaster.configuration.changed';
    }

    /**
     * Get the event data
     *
     * @return array
     */
    public function getEventData()
    {
        return [
            'coaster_id' => $this->coasterId
        ];
    }

    /**
     * Get the event timestamp
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get the coaster ID
     *
     * @return string
     */
    public function getCoasterId()
    {
        return $this->coasterId;
    }
}
