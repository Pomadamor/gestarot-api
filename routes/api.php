<?php

use Illuminate\Http\Request;
use App\Http\Middleware\ApiAuthenticate;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/user/login', 'User@login');

Route::post( '/logged_user', 'User@updateUser' )
    ->middleware(ApiAuthenticate::class);

Route::post('/user/{id}', 'User@updateUser')
    ->middleware(ApiAuthenticate::class);

Route::delete('/user/{id}', 'User@deleteUser')
    ->middleware(ApiAuthenticate::class);

Route::get('/logged_user', 'User@get' )
    ->where('id', '[0-9]+')
    ->middleware(ApiAuthenticate::class);

Route::get('/user/{id}', 'User@get' )
    ->where('id', '[0-9]+')
    ->middleware(ApiAuthenticate::class);

Route::get('/user/{id}/friends', 'User@getFriends')
    ->where('id', '[0-9]+')
    ->middleware(ApiAuthenticate::class);

Route::post('/user/{id}/friends', 'User@addFriend')
    ->where('id', '[0-9]+')
    ->where('friend_id', '[0-9]+')
    ->middleware(ApiAuthenticate::class);