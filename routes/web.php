<?php

// Auth Routes
Route::group(['namespace' => 'Auth'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::get('login', ['as' => 'login', 'uses' => 'LoginController@getLogin']);
        Route::post('login', ['uses' => 'LoginController@postLogin']);
        Route::get('register', ['as' => 'register', 'uses' => 'RegisterController@getRegister']);
        Route::post('register', ['uses' => 'RegisterController@postRegister']);
        Route::get('forget/password', ['as' => 'forget_pwd', 'uses' => 'RegisterController@getForgetPassword']);
        Route::post('forget/password', ['uses' => 'RegisterController@postForgetPassword']);
        Route::post('edit', ['uses' => 'AuthController@edit']);
        Route::get('logout', ['as' => 'logout', 'uses' => 'LoginController@logout']);
    });
    Route::get('/', ['as' => 'homepage', 'uses' => 'AuthController@index']);
});

// User Routes
Route::group(['namespace' => 'User', 'prefix' => 'user'], function () {
    Route::get('listAccount', ['uses' => 'AccountController@listAccount']);
    Route::get('editAccount/{account_id}', ['uses' => 'AccountController@editAccount']);
    Route::post('updateAccount/{account_id}', ['uses' => 'AccountController@updateAccount']);
    Route::get('resetPassword/{account_id}', ['uses' => 'AccountController@resetPassword']);

    Route::get('listRole', ['uses' => 'AuthorityController@listRole']);
    Route::get('createRole', ['uses' => 'AuthorityController@createRole']);
    Route::post('saveRole', ['uses' => 'AuthorityController@saveRole']);
    Route::get('editRole/{role_id}', ['uses' => 'AuthorityController@editRole']);
    Route::post('updateRole/{role_id}', ['uses' => 'AuthorityController@updateRole']);
    Route::get('editRolePower/{role_id}', ['uses' => 'AuthorityController@editRolePower']);
    Route::post('updateRolePower/{role_id}', ['uses' => 'AuthorityController@updateRolePower']);

    Route::get('listPower', ['uses' => 'AuthorityController@listPower']);
    Route::get('initRoute', ['uses' => 'AuthorityController@initRoute']);
    Route::get('editPower/{power_id}', ['uses' => 'AuthorityController@editPower']);
    Route::post('updatePower/{power_id}', ['uses' => 'AuthorityController@updatePower']);
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

// Monitor Routes
Route::get('monitor/table', ['uses' => 'MonitorController@table']);
Route::get('monitor/device', ['uses' => 'MonitorController@device']);
Route::get('monitor/order', ['uses' => 'MonitorController@order']);
Route::get('monitor/circleTable', ['uses' => 'MonitorController@circleTable']);
Route::get('monitor/zabbix', ['uses' => 'MonitorController@zabbix']);

// Sql Analyze Routes
Route::group(['middleware' => 'cache.rows'], function () {
    Route::get('analyze/{type}/{group}/{auth?}', ['uses' => 'SqlController@analyze']);
    Route::get('query/id/{id}', ['uses' => 'SqlController@queryId']);
    Route::get('ajax/query/sql', ['uses' => 'SqlController@ajaxQuerySql']);
    Route::get('query/sql', ['uses' => 'SqlController@querySql']);
    Route::get('query/empty', ['uses' => 'SqlController@emptySql']);
});

// Slow Routes
Route::get('slow_rpc', ['uses' => 'SlowController@rpc']);
Route::get('slow_mysql', ['uses' => 'SlowController@mysql']);

// Redis Routes
Route::get('redis_throttle', ['uses' => 'RedisController@throttle']);

// Tool Routes
Route::get('tool/download', ['uses' => 'ToolController@getDownload']);
Route::post('tool/download', ['uses' => 'ToolController@postDownload']);
Route::post('ajax/tool/download', ['uses' => 'ToolController@ajaxDownload']);
Route::get('tool/upload', ['uses' => 'ToolController@getUpload']);
Route::post('ajax/tool/upload', ['uses' => 'ToolController@ajaxUpload']);

// System Routes
Route::get('system/config', ['uses' => 'SystemController@getConfig']);
Route::post('system/config', ['uses' => 'SystemController@postConfig']);

// Export Routes
Route::group(['namespace' => 'Export', 'prefix' => 'export'], function () {
    Route::get('school', ['uses' => 'SchoolController@school']);
    Route::post('school', ['uses' => 'SchoolController@export']);

    Route::get('student', ['uses' => 'StudentController@student']);
    Route::post('student', ['uses' => 'StudentController@export']);

    Route::get('single', ['uses' => 'SingleController@single']);
    Route::post('ajax/single', ['uses' => 'SingleController@export']);
});

// Logs Routes
Route::get('logs', ['uses' => 'LogController@logs']);

// Select Routes
Route::group(['namespace' => 'Select', 'prefix' => 'select'], function () {
    Route::get('marketer', ['uses' => 'ShowController@marketer']);
    Route::get('labels', ['uses' => 'ShowController@labels']);
    Route::get('feedback', ['uses' => 'ShowController@feedback']);
    Route::get('abnormal', ['uses' => 'ShowController@abnormal']);

    Route::get('wordbank', ['uses' => 'SearchController@wordbank']);
    Route::get('quit_student', ['as' => 'select_quit_student', 'uses' => 'SearchController@quit_student']);
    Route::get('yellow_account', ['uses' => 'SearchController@yellow_account']);
});

// Database Routes
Route::group(['namespace' => 'Database', 'prefix' => 'database'], function () {
    Route::group(['prefix' => 'get'], function () {
        Route::get('tableList', ['uses' => 'TableController@getTableList']);
        Route::get('tableInfo/{table_name}', ['uses' => 'TableController@getTableInfo']);
    });
    Route::get('diff', ['uses' => 'DiffController@diff']);

    Route::get('table_correct', ['uses' => 'DiffController@table_correct']);
});