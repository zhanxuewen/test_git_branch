<?php

// Auth Routes
Route::group(['namespace' => 'Auth'], function () {
    Route::get('/', ['as' => 'homepage', 'uses' => 'LoginController@index']);
    Route::get('login', ['as' => 'login', 'uses' => 'LoginController@getLogin']);
    Route::post('login', ['uses' => 'LoginController@postLogin']);
    Route::get('register', ['as' => 'register', 'uses' => 'RegisterController@getRegister']);
    Route::post('register', ['uses' => 'RegisterController@postRegister']);
    Route::get('logout', ['uses' => 'LoginController@logout']);
});

// Rpc Routes
Route::group(['namespace' => 'Rpc'], function () {
    Route::group(['prefix' => 'db'], function () {
        Route::group(['prefix' => 'get'], function () {
            Route::get('modelList', 'DBController@getModelList');
            Route::get('modelInfo/{model_id}', 'DBController@getModelInfo');
        });
    });
    
    Route::group(['prefix' => 'repo'], function () {
        Route::group(['prefix' => 'get'], function () {
            Route::get('repositoryList', 'RepoController@getRepositoryList');
            Route::get('functionInfo/{function_id}', 'RepoController@getFunctionInfo');
        });
    });
    Route::group(['prefix' => 'service'], function () {
        Route::group(['prefix' => 'get'], function () {
            Route::get('serviceList', 'ServiceController@getServiceList');
            Route::get('apiInfo/{api_id}', 'ServiceController@getApiInfo');
        });
    });
});

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
Route::get('table_diff', ['uses' => 'DatabaseController@table_diff']);
Route::get('table_correct', ['uses' => 'DatabaseController@table_correct']);