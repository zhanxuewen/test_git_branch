<?php

// Auth Routes
Route::group(['namespace' => 'Auth'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::get('login', ['as' => 'login', 'uses' => 'LoginController@getLogin']);
        Route::post('login', ['uses' => 'LoginController@postLogin']);
        Route::get('register', ['as' => 'register', 'uses' => 'RegisterController@getRegister']);
        Route::post('register', ['uses' => 'RegisterController@postRegister']);
        Route::post('edit', ['uses' => 'AuthController@edit']);
        Route::get('logout', ['as' => 'logout', 'uses' => 'LoginController@logout']);
    });
    Route::get('/', ['as' => 'homepage', 'uses' => 'AuthController@index']);
});

Route::get('dashboard', ['as' => 'dashboard', 'uses' => 'DashboardController@dashboard']);

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

Route::get('monitor/table', ['uses' => 'MonitorController@table']);
Route::get('monitor/device', ['uses' => 'MonitorController@device']);

Route::get('analyze/{type}/{group}/{auth?}', ['uses' => 'SqlController@analyze']);
Route::get('query/id/{id}', ['uses' => 'SqlController@queryId']);
Route::get('ajax/query/sql', ['uses' => 'SqlController@ajaxQuerySql']);
Route::get('query/sql', ['uses' => 'SqlController@querySql']);
Route::get('query/empty', ['uses' => 'SqlController@emptySql']);

Route::get('slow_rpc', ['uses' => 'SlowController@rpc']);
Route::get('slow_mysql', ['uses' => 'SlowController@mysql']);

Route::get('redis_throttle', ['uses' => 'RedisController@throttle']);

// Export Routes
Route::group(['namespace' => 'Export', 'prefix' => 'export'], function () {
    Route::get('school', ['uses' => 'SchoolController@school']);
    Route::post('school', ['uses' => 'SchoolController@export']);
    
    Route::get('student', ['uses' => 'StudentController@student']);
    Route::post('student', ['uses' => 'StudentController@export']);
});

Route::get('logs', ['uses' => 'LogController@logs']);

Route::group(['namespace' => 'Select', 'prefix' => 'select'], function () {
    Route::get('marketer', ['uses' => 'ShowController@marketer']);
    Route::get('labels', ['uses' => 'ShowController@labels']);
    Route::get('feedback', ['uses' => 'ShowController@feedback']);
    Route::get('quit_student', ['as' => 'select_quit_student', 'uses' => 'SearchController@quit_student']);
});

Route::group(['namespace' => 'Database', 'prefix' => 'database'], function () {
    Route::group(['prefix' => 'get'], function () {
        Route::get('tableList', ['uses' => 'TableController@getTableList']);
        Route::get('tableInfo/{table_name}', ['uses' => 'TableController@getTableInfo']);
    });
    Route::get('diff', ['uses' => 'DiffController@diff']);
    
    Route::get('table_correct', ['uses' => 'DiffController@table_correct']);
});


