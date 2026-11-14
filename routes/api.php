<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

// Inherited token API routes are disabled during the TASK-024 hierarchy/access milestone
// until scoped API authentication, 2FA parity and throttling are defined.
Route::prefix('v1')->any('{any?}', function (): JsonResponse {
    return response()->json([
        'message' => 'API token endpoints are disabled for this milestone. Use the scoped web workspace.',
    ], 403);
})->where('any', '.*');
