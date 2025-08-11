<?php

namespace App\Controllers;

use App\FleetManagement\Domain\Wagon;
use App\FleetManagement\Infrastructure\RedisCoasterRepository;
use CodeIgniter\RESTful\ResourceController;

/**
 * Wagon Controller
 * Handles API requests for wagons
 */
class WagonController extends ResourceController
{
    /**
     * @var RedisCoasterRepository
     */
    private $repository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->repository = new RedisCoasterRepository();
    }

    /**
     * Create a new wagon for a coaster
     *
     * @param string $coasterId
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function create($coasterId = null)
    {
        $json = $this->request->getJSON(true);

        // Validate request
        if (!$this->validateData($json, $this->validator->getRuleGroup('wagon.create'))) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $coaster = $this->repository->findById($coasterId);

            if (!$coaster) {
                return $this->failNotFound('Coaster not found');
            }

            $wagon = Wagon::create(
                $json['seat_count'],
                $json['speed_mps']
            );

            $coaster->addWagon($wagon);
            $this->repository->save($coaster);

            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Wagon added successfully',
                'data' => [
                    'id' => $wagon->getId()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Delete a wagon from a coaster
     *
     * @param string $coasterId
     * @param string $wagonId
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function delete($coasterId = null, $wagonId = null)
    {
        try {
            $coaster = $this->repository->findById($coasterId);

            if (!$coaster) {
                return $this->failNotFound('Coaster not found');
            }

            $coaster->removeWagon($wagonId);
            $this->repository->save($coaster);

            return $this->respondDeleted([
                'status'  => 'success',
                'message' => 'Wagon removed successfully'
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->failNotFound($e->getMessage()); // Use the exception message
        } catch (\Exception $e) {
            log_message('error', '[WAGON_DELETE_ERROR] ' . $e->getMessage());
            return $this->failServerError('An unexpected error occurred.');
        }
    }
}