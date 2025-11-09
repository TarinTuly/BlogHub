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
    Route::get('/users', [AuthController::class, 'getAllUsers']);
    Route::get('/users/{id}', [AuthController::class, 'getUserById']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
