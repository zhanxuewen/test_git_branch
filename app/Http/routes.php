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
Route::get('ajax/query/sql', ['uses' => 'SqlController@ajaxQuerySql']);
Route::get('query/sql', ['uses' => 'SqlController@querySql']);
Route::get('query/delete', ['uses' => 'SqlController@querySql']);

Route::get('slow_rpc', ['uses' => 'SlowController@rpc']);
Route::get('slow_mysql', ['uses' => 'SlowController@mysql']);

Route::get('redis_throttle', ['uses' => 'RedisController@throttle']);

Route::group(['namespace' => 'Export', 'prefix' => 'export'], function () {
    Route::get('school', ['uses' => 'SchoolController@school']);
    Route::post('school', ['uses' => 'SchoolController@export']);
    
    Route::get('student', ['uses' => 'StudentController@student']);
    Route::post('student', ['uses' => 'StudentController@export']);
});

Route::get('logs', ['uses' => 'LogController@logs']);

Route::get('marketer', ['uses' => 'SelectController@marketer']);
Route::get('labels', ['uses' => 'SelectController@labels']);
Route::get('quit_student', ['uses' => 'SelectController@quit_student']);

Route::group(['namespace' => 'Database', 'prefix' => 'database'], function () {
    Route::group(['prefix' => 'get'], function () {
        Route::get('tableList', ['uses' => 'TableController@getTableList']);
        Route::get('tableInfo/{table_name}', ['uses' => 'TableController@getTableInfo']);
    });
    Route::get('diff', ['uses' => 'DiffController@diff']);
    
    Route::get('table_correct', ['uses' => 'DiffController@table_correct']);
});


