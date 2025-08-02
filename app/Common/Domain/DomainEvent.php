<?php

namespace App\Common\Domain;

/**
 * Domain Event Interface
 * Base interface for all domain events
 */
interface DomainEvent
{
    /**
     * Get the event name
     *
     * @return string
     */
    public function getEventName();

    /**
     * Get the event data
     *
     * @return array
     */
    public function getEventData();

    /**
     * Get the event timestamp
     *
     * @return int
     */
    public function getTimestamp();
}
