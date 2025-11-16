<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostLikeController;

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




Route::middleware('auth:sanctum')->group(function () {
    Route::get('posts', [PostController::class, 'index']);
    Route::post('posts', [PostController::class, 'store']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::get('/posts/others', [PostController::class, 'otherPosts']);
});





// All routes protected by auth:sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Get all comments for a post
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);

    // Add a comment or reply
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);

    // Update a comment (only owner)
    Route::put('/comments/{comment}', [CommentController::class, 'update']);

    // Delete a comment and its replies (only owner)
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->post('/posts/{post}/like', [PostLikeController::class, 'toggle']);


