<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    ContentController,
    PairController,
    AgentStatusController,
    MissionAttemptController,
    ParentPinController,
    HouseholdEmailController,
    TeacherAuthController,
    TeacherAnalyticsController
};

Route::prefix('v1')->group(function () {
    Route::get('/content/{series}/{book}/s/{seasonNo}', [ContentController::class, 'show']);
    Route::post('/pair', [PairController::class, 'pair']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/agent/status', [AgentStatusController::class, 'show']);
        Route::post('/missions/{missionId}/attempt', [MissionAttemptController::class, 'attempt']);

        Route::post('/parent/pin/set', [ParentPinController::class, 'set']);
        Route::post('/parent/pin/verify', [ParentPinController::class, 'verify']);

        Route::middleware('ability:parent')->group(function () {
            Route::post('/household/email/setup', [HouseholdEmailController::class, 'setup']);
        });
    });

    Route::post('/household/email/verify', [HouseholdEmailController::class, 'verify']);
    Route::post('/household/email/unsubscribe', [HouseholdEmailController::class, 'unsubscribe']);

    Route::prefix('teacher')->group(function () {
        Route::post('/auth/login', [TeacherAuthController::class, 'login']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/classes/{classId}/analytics', [TeacherAnalyticsController::class, 'show']);
        });
    });
});
