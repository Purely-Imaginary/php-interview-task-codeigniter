<?php

namespace App\Common\Traits;

/**
 * API Response Trait
 * Standardizes API responses across the application
 */
trait ApiResponseTrait
{
    /**
     * Return a standardized success response
     *
     * @param array $data The data to return
     * @param string $message Success message
     * @param int $code HTTP status code
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    protected function respondSuccess($data = [], string $message = 'Success', int $code = 200)
    {
        return $this->respond([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Return a standardized created response
     *
     * @param array $data The data to return
     * @param string $message Success message
     * @param int $code HTTP status code
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    protected function respondCreatedSuccess(
        $data = [],
        string $message = 'Resource created successfully',
        int $code = 201
    ) {
        return $this->respondSuccess($data, $message, $code);
    }

    /**
     * Return a standardized error response
     *
     * @param string $message Error message
     * @param array $errors Detailed errors
     * @param int $code HTTP status code
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    protected function respondError(string $message = 'Error', array $errors = [], int $code = 400)
    {
        return $this->respond([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
}
