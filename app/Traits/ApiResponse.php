<?php

namespace App\Traits;

trait ApiResponse
{
    protected function successResponse($data = null, $messageKey = null, $statusCode = 200)
    {
        $response = ['success' => true];

        if ($messageKey) {
            $response['message'] = __($messageKey);
        }

        if ($data) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    protected function errorResponse($messageKey, $errors = [], $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => __($messageKey),
            'errors' => $errors
        ], $statusCode);
    }
}
