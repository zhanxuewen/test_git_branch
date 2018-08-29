<?php

Route::get('routes', function () {
    return view('routes');
});

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
Route::get('migrations', ['uses' => 'SelectController@migration_diff']);