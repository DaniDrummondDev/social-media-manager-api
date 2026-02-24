<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Http\Resources;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * Success response following JSON:API simplified format.
     *
     * @param  mixed  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        $response = ['data' => $data];

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return new JsonResponse($response, $status);
    }

    /**
     * Error response with application error codes.
     *
     * @param  array<int, array{code: string, message: string, field?: string}>  $errors
     */
    public static function error(array $errors, int $status = 400): JsonResponse
    {
        return new JsonResponse(['errors' => $errors], $status);
    }

    /**
     * Single error shorthand.
     */
    public static function fail(string $code, string $message, int $status = 400): JsonResponse
    {
        return self::error([['code' => $code, 'message' => $message]], $status);
    }

    /**
     * No content response (204).
     */
    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }
}
