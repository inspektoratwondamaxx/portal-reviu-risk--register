<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

/** Format response API baku sesuai Bab VI.1 kajian teknis. */
abstract class Controller
{
    use AuthorizesRequests;

    protected function ok(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function fail(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
