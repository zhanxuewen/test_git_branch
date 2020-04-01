<?php

Route::group(['namespace' => 'Database', 'prefix' => 'database'], function () {
    Route::get('DBWiki', ['uses' => 'TableController@getDBWiki']);
    Route::post('DBWiki', ['uses' => 'TableController@editDBWiki']);
    Route::get('diff', ['uses' => 'DiffController@diff']);
    Route::get('migration/history', ['uses' => 'MigrationController@history']);
    Route::get('ajax/migration/table', ['uses' => 'MigrationController@ajaxTable']);

    Route::get('table_correct', ['uses' => 'DiffController@table_correct']);
});