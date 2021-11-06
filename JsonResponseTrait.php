<?php

namespace App\Traits;

trait JsonResponseTrait
{
    public function jsonSuccessResponse(?array $data = null, ?string $message = null, int $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function jsonFailureResponse(?string $message = null, mixed $error = null, int $code = 400): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'failure',
            'message' => $message,
            'error' => $error,
        ], $code);
    }

    public function jsonResponseCollection(\Illuminate\Pagination\LengthAwarePaginator $collection, int $code = 200): \Illuminate\Http\JsonResponse
    {
        $data = [
            'items' => $collection->items(),
            'meta' => [
                'total_items' => $collection->total(),
                'current_page' => $collection->currentPage(),
                'per_page' => $collection->perPage(),
                'total_pages' => $collection->lastPage(),
            ],
        ];
        return $this->jsonSuccessResponse($data, null, $code);
    }

    public function jsonWrongPasswordResponse(): \Illuminate\Http\JsonResponse
    {
        return $this->jsonFailureResponse('wrong password', ['password' => 'wrong password'], 422);
    }

    public function jsonInvalidLoginCredentialsResponse(): \Illuminate\Http\JsonResponse
    {
        return $this->jsonFailureResponse(__('auth.failed'), null, 422);
    }

    public function jsonIdenticalPasswordResponse(): \Illuminate\Http\JsonResponse
    {
        return $this->jsonFailureResponse('identical passwords', ['password' => 'current password and new password are identical'], 422);
    }

    public function jsonValidationErrorResponse(mixed $error = null): \Illuminate\Http\JsonResponse
    {
        return $this->jsonFailureResponse('validation error', $error, 422);
    }
}
