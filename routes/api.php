<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/t', function () {
    return view('auth.index');
});

Route::get('/',function(){
    return "api";
});


Route::apiResource('post',PostController::class);
Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Get all users
    Route::get('/users', [AuthController::class, 'getAllUsers']);

    // Get a single user
    Route::get('/users/{id}', [AuthController::class, 'getUserById']);

    // Update a user
    Route::put('/users/{id}', [AuthController::class, 'updateUser']);

    // Delete a user
    Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);

    // Get the logged-in user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

