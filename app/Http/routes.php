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
Route::get('query/id/{id}', ['uses' => 'NewSqlController@queryId']);
Route::get('query/sql', ['uses' => 'NewSqlController@querySql']);

Route::get('slow_rpc', ['uses' => 'SlowController@rpc']);
Route::get('slow_mysql', ['uses' => 'SlowController@mysql']);

Route::get('redis_throttle', ['uses' => 'RedisController@throttle']);

Route::get('export', ['uses' => 'ExportController@index']);
Route::post('export', ['uses' => 'ExportController@export']);

Route::get('select', ['uses' => 'SelectController@select']);
Route::get('labels', ['uses' => 'SelectController@labels']);