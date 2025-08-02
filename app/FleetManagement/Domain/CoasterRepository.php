<?php

namespace App\FleetManagement\Domain;

/**
 * Coaster Repository Interface
 * Defines the contract for persisting and retrieving Coaster aggregates
 */
interface CoasterRepository
{
    /**
     * Save a coaster
     *
     * @param Coaster $coaster
     * @return void
     */
    public function save(Coaster $coaster);

    /**
     * Find a coaster by ID
     *
     * @param string $id
     * @return Coaster|null
     */
    public function findById($id);

    /**
     * Find all coasters
     *
     * @return array
     */
    public function findAll();

    /**
     * Delete a coaster
     *
     * @param string $id
     * @return void
     */
    public function delete($id);

    /**
     * Publish domain events
     *
     * @param array $events
     * @return void
     */
    public function publishEvents(array $events);
}
