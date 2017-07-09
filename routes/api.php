<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/auth', 'AuthController@index');
Route::post('/ping', 'GatewayController@ping');
Route::get('/ping', 'GatewayController@ping'); // wifidog-gateway is wrong, it should use post not get.
