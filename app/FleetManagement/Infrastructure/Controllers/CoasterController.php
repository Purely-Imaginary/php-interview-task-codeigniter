<?php

namespace App\FleetManagement\Infrastructure\Controllers;

use App\FleetManagement\Domain\Coaster;
use App\FleetManagement\Infrastructure\RedisCoasterRepository;
use App\Common\Domain\TimeRange;
use App\Common\Traits\ApiResponseTrait;
use CodeIgniter\RESTful\ResourceController;

/**
 * Coaster Controller
 * Handles API requests for coasters
 */
class CoasterController extends ResourceController
{
    use ApiResponseTrait;

    /**
     * @var RedisCoasterRepository
     */
    private $repository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->repository = service('coasterRepository');
    }

    /**
     * Create a new coaster
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function create()
    {
        $json = $this->request->getJSON(true);

        // Validate request
        if (!$this->validateCoasterData($json)) {
            return $this->respondError('Validation failed', $this->validator->getErrors(), 400);
        }

        try {
            $coaster = Coaster::create(
                $json['personnel_count'],
                $json['daily_clients'],
                $json['track_length_meters'],
                $json['operating_hours_start'],
                $json['operating_hours_end']
            );

            $this->repository->save($coaster);

            return $this->respondCreatedSuccess([
                'id' => $coaster->getId()
            ], 'Coaster created successfully');
        } catch (\Exception $e) {
            return $this->respondError('An error occurred', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a coaster
     *
     * @param string $id
     * @return \CodeIgniter\HTTP\Response
     */
    public function update($id = null)
    {
        $json = $this->request->getJSON(true);

        // Validate request
        if (!$this->validateCoasterUpdateData($json)) {
            return $this->respondError('Validation failed', $this->validator->getErrors(), 400);
        }

        try {
            $coaster = $this->repository->findById($id);

            if (!$coaster) {
                return $this->respondError('Coaster not found', [], 404);
            }

            $coaster->update(
                $json['personnel_count'],
                $json['daily_clients'],
                new TimeRange($json['operating_hours_start'], $json['operating_hours_end'])
            );

            $this->repository->save($coaster);

            return $this->respondSuccess([], 'Coaster updated successfully');
        } catch (\Exception $e) {
            return $this->respondError('An error occurred', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Validate coaster data for creation
     *
     * @param array $data
     * @return bool
     */
    private function validateCoasterData($data)
    {
        $rules = [
            'personnel_count' => 'required|integer|greater_than[0]',
            'daily_clients' => 'required|integer|greater_than[0]',
            'track_length_meters' => 'required|integer|greater_than[0]',
            'operating_hours_start' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
            'operating_hours_end' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]'
        ];

        $this->validator = \Config\Services::validation();
        $this->validator->setRules($rules);

        return $this->validator->run($data);
    }

    /**
     * Validate coaster data for update
     *
     * @param array $data
     * @return bool
     */
    private function validateCoasterUpdateData($data)
    {
        $rules = [
            'personnel_count' => 'required|integer|greater_than[0]',
            'daily_clients' => 'required|integer|greater_than[0]',
            'operating_hours_start' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]',
            'operating_hours_end' => 'required|regex_match[/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/]'
        ];

        $this->validator = \Config\Services::validation();
        $this->validator->setRules($rules);

        return $this->validator->run($data);
    }
}
