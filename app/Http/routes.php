<?php

Route::get('/', function () {
    return view('frame.homepage');
});

Route::get('analyze/{type}/{group}/{auth?}', ['uses' => 'SqlController@analyze']);
Route::get('query/id/{id}', ['uses' => 'SqlController@queryId']);
Route::get('query/sql', ['uses' => 'SqlController@querySql']);

Route::get('slow_rpc', ['uses' => 'SlowController@rpc']);
Route::get('slow_mysql', ['uses' => 'SlowController@mysql']);

Route::get('redis_throttle', ['uses' => 'RedisController@throttle']);

Route::get('export', ['uses' => 'ExportController@index']);
Route::post('export', ['uses' => 'ExportController@export']);

Route::get('select', ['uses' => 'SelectController@marketer']);
Route::get('labels', ['uses' => 'SelectController@labels']);

Route::get('migrations', ['uses' => 'DatabaseController@migration_diff']);
Route::get('table_correct', ['uses' => 'DatabaseController@table_correct']);