<?php

namespace App\OperationalAnalysis\Domain;

use App\FleetManagement\Domain\Coaster;

/**
 * Personnel Analysis Service
 * Analyzes personnel requirements for a coaster
 */
class PersonnelAnalysisService
{
    /**
     * Analyze personnel requirements
     *
     * @param Coaster $coaster
     * @return array
     */
    public function analyze(Coaster $coaster)
    {
        // Rule: 1 staff member is required to operate the Coaster,
        // plus 2 additional staff members for each assigned Wagon
        $wagons = $coaster->getWagons();
        $requiredPersonnel = 1 + (count($wagons) * 2);
        $availablePersonnel = $coaster->getPersonnelCount();

        $shortage = max(0, $requiredPersonnel - $availablePersonnel);
        $surplus = max(0, $availablePersonnel - $requiredPersonnel);

        return [
            'required_personnel' => $requiredPersonnel,
            'available_personnel' => $availablePersonnel,
            'shortage' => $shortage,
            'surplus' => $surplus,
            'has_shortage' => $shortage > 0,
            'has_surplus' => $surplus > 0
        ];
    }

    /**
     * Calculate additional personnel needed
     *
     * @param int $currentPersonnel
     * @param int $wagonCount
     * @return int
     */
    public function calculateAdditionalPersonnelNeeded($currentPersonnel, $wagonCount)
    {
        $requiredPersonnel = 1 + ($wagonCount * 2);
        return max(0, $requiredPersonnel - $currentPersonnel);
    }

    /**
     * Calculate maximum wagons that can be operated
     *
     * @param int $availablePersonnel
     * @return int
     */
    public function calculateMaxWagons($availablePersonnel)
    {
        // Subtract 1 for the base staff member required for the coaster
        $remainingPersonnel = $availablePersonnel - 1;

        // Each wagon requires 2 staff members
        return floor($remainingPersonnel / 2);
    }
}
