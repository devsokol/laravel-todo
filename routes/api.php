<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\TaskController;


/*
|---------------------------------------------------------------------`-----
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('/user/{userId}')->group(function () {
    Route::apiResource('tasks', TaskController::class)->only([
        'index',
        'destroy',
        'update',
        'store'
    ]);

    Route::post('/tasks/{taskId}/complete', [TaskController::class, 'markAsDone']);
});
