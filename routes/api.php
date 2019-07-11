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

Route::get('/user', 'User@get_all');
    //->middleware(ApiAuthenticate::class);

Route::post( '/user', 'User@create' );

Route::post('/user/{id}', 'User@updateUser')
    ->middleware(ApiAuthenticate::class);

Route::delete('/user/{id}', 'User@deleteUser')
    ->middleware(ApiAuthenticate::class);

// Route::middleware('auth:api')->get( '/user/{id}', 'User@get' )
//     ->where('id', '[0-9]+');
