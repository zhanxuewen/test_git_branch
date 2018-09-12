<?php

Route::get('/', ['as' => 'homepage', 'uses' => 'LoginController@index']);
Route::post('login', ['uses' => 'LoginController@login']);
Route::post('register', ['uses' => 'LoginController@register']);
Route::get('logout', ['uses' => 'LoginController@logout']);

Route::get('analyze/{type}/{group}/{auth?}', ['uses' => 'SqlController@analyze']);
Route::get('query/id/{id}', ['uses' => 'SqlController@queryId']);
Route::get('query/sql', ['uses' => 'SqlController@querySql']);

Route::get('slow_rpc', ['uses' => 'SlowController@rpc']);
Route::get('slow_mysql', ['uses' => 'SlowController@mysql']);

Route::get('redis_throttle', ['uses' => 'RedisController@throttle']);

Route::get('export', ['uses' => 'ExportController@index']);
Route::post('export', ['uses' => 'ExportController@export']);

Route::get('logs', ['uses' => 'LogController@logs']);

Route::get('marketer', ['uses' => 'SelectController@marketer']);
Route::get('labels', ['uses' => 'SelectController@labels']);
Route::get('quit_student', ['uses' => 'SelectController@quit_student']);

Route::get('migrations', ['uses' => 'DatabaseController@migration_diff']);
Route::get('table_correct', ['uses' => 'DatabaseController@table_correct']);