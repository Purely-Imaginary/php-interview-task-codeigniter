<?php

namespace App\OperationalAnalysis\Domain;

use App\FleetManagement\Domain\Coaster;

/**
 * Throughput Analysis Service
 * Analyzes client throughput for a coaster
 */
class ThroughputAnalysisService
{
    /**
     * Break time in minutes after each wagon trip
     */
    private const BREAK_TIME_MINUTES = 5;

    /**
     * Analyze client throughput
     *
     * @param Coaster $coaster
     * @return array
     */
    public function analyze(Coaster $coaster)
    {
        $wagons = $coaster->getWagons();
        $operatingHours = $coaster->getOperatingHours();
        $trackLength = $coaster->getTrackLength();
        $dailyClients = $coaster->getDailyClients();

        // Calculate total operating minutes
        $operatingMinutes = $operatingHours->getDurationInMinutes();

        // Calculate total seat capacity across all wagons
        $totalSeatCapacity = 0;
        $totalTripsPerDay = 0;
        $totalClientsPerDay = 0;

        foreach ($wagons as $wagon) {
            // Calculate time for one cycle: (track_length / wagon_speed) + break time
            $tripTimeSeconds = $wagon->getSpeed()->calculateTimeToTravel($trackLength);
            $tripTimeMinutes = ceil($tripTimeSeconds / 60) + self::BREAK_TIME_MINUTES;

            // Calculate trips per day for this wagon
            $tripsPerDay = floor($operatingMinutes / $tripTimeMinutes);

            // Calculate clients per day for this wagon
            $clientsPerDay = $tripsPerDay * $wagon->getSeatCount()->getSeats();

            $totalSeatCapacity += $wagon->getSeatCount()->getSeats();
            $totalTripsPerDay += $tripsPerDay;
            $totalClientsPerDay += $clientsPerDay;
        }

        // Calculate shortage or surplus
        $shortage = max(0, $dailyClients - $totalClientsPerDay);
        $surplus = max(0, $totalClientsPerDay - ($dailyClients * 2)); // Surplus if more than twice the daily clients

        // Calculate additional resources needed if there's a shortage
        $additionalWagonsNeeded = 0;
        if ($shortage > 0 && $totalSeatCapacity > 0) {
            // Estimate additional wagons needed based on average capacity
            $averageWagonCapacity = $totalSeatCapacity / count($wagons);
            $averageTripsPerWagon = $totalTripsPerDay / count($wagons);
            $clientsPerWagon = $averageWagonCapacity * $averageTripsPerWagon;

            $additionalWagonsNeeded = ceil($shortage / $clientsPerWagon);
        }

        return [
            'total_seat_capacity' => $totalSeatCapacity,
            'total_trips_per_day' => $totalTripsPerDay,
            'total_clients_per_day' => $totalClientsPerDay,
            'daily_clients_target' => $dailyClients,
            'shortage' => $shortage,
            'surplus' => $surplus,
            'has_shortage' => $shortage > 0,
            'has_surplus' => $surplus > 0,
            'additional_wagons_needed' => $additionalWagonsNeeded
        ];
    }

    /**
     * Calculate the number of clients that can be served per day
     *
     * @param int $operatingMinutes
     * @param int $seatCapacity
     * @param int $tripTimeMinutes
     * @return int
     */
    public function calculateClientsPerDay($operatingMinutes, $seatCapacity, $tripTimeMinutes)
    {
        $tripsPerDay = floor($operatingMinutes / $tripTimeMinutes);
        return $tripsPerDay * $seatCapacity;
    }
}
