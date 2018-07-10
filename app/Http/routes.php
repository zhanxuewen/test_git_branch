<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('sql', ['uses' => 'SqlController@index']);
Route::get('newSql', ['uses' => 'NewSqlController@index']);
Route::get('rpc_slow', ['uses' => 'RpcController@slow']);

Route::get('export', ['uses' => 'ExportController@index']);
Route::post('export', ['uses' => 'ExportController@export']);