<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('auth.index');
});

Route::get('/welcome', function () {
    $user = Auth::user();
    return view('welcome', compact('user'));

});

