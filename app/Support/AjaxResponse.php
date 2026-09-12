<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class AjaxResponse
{
    public static function success(mixed $data = [], string $message = 'OK', int $status = 200): JsonResponse
    {
        return self::payload('success', $message, $data, $status);
    }

    public static function error(string $message, mixed $data = [], int $status = 422): JsonResponse
    {
        return self::payload('error', $message, $data, $status);
    }

    public static function failure(string $message = 'Server error.', mixed $data = [], int $status = 500): JsonResponse
    {
        return self::payload('failure', $message, $data, $status);
    }

    private static function payload(string $status, string $message, mixed $data, int $code): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
