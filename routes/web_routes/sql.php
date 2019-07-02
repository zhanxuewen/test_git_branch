<?php

Route::get('analyze/{type}/{group}/{auth?}', ['uses' => 'SqlController@analyze']);
Route::get('query/id/{id}', ['uses' => 'SqlController@queryId']);
Route::get('ajax/query/sql', ['uses' => 'SqlController@ajaxQuerySql']);
Route::get('query/sql', ['uses' => 'SqlController@querySql']);
Route::get('query/empty', ['uses' => 'SqlController@emptySql']);