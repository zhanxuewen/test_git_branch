<?php

Route::group(['prefix' => 'get'], function () {
    Route::get('tableList', ['uses' => 'TableController@getTableList']);
    Route::get('tableInfo/{table_name}', ['uses' => 'TableController@getTableInfo']);
});
Route::get('diff', ['uses' => 'DiffController@diff']);
Route::get('migration/history', ['uses' => 'MigrationController@history']);
Route::get('ajax/migration/table', ['uses' => 'MigrationController@ajaxTable']);

Route::get('table_correct', ['uses' => 'DiffController@table_correct']);