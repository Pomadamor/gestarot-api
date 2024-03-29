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

Route::post( '/user', 'User@create' );

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

Route::get('/logged_user/friends', 'User@getFriends')
    ->middleware(ApiAuthenticate::class);

Route::post('/user/{id}/friends', 'User@addFriend')
    ->where('id', '[0-9]+')
    ->middleware(ApiAuthenticate::class);

Route::post('/logged_user/friends', 'User@addFriend')
    ->middleware(ApiAuthenticate::class);

Route::delete('/logged_user/friends/{id}', 'User@deleteFriend')
    ->where('id', '[0-9]+')
    ->middleware(ApiAuthenticate::class);

# id is optional: if it exists, update the game in place of creating it
Route::post('/game/{id?}', 'Game@createOrUpdate')
    ->middleware(ApiAuthenticate::class);

Route::get('/game/{id}', 'Game@get')
    ->where('id', '[0-9]+')
    ->middleware(ApiAuthenticate::class);

Route::get('/history/party', 'Game@getAllLoggedUser')
    ->middleware(ApiAuthenticate::class);

Route::delete('/game/{id}', 'Game@delete')
    ->middleware(ApiAuthenticate::class);

Route::get('/admin/games', 'Game@get_all');
Route::get('/admin/user', 'User@get_all');