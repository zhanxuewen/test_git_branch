<?php

Route::group([], function () {
    $routes = ['user', 'auth', 'export', 'monitor', 'sql', 'select', 'database', 'tool', 'bank'];
    foreach ($routes as $route) {
        include base_path('routes/web_routes/') . $route . '.php';
    }
});

Route::get('dashboard', ['as' => 'dashboard', 'uses' => 'DashboardController@dashboard']);

Route::get('diagrams/uml', ['uses' => 'DiagramController@uml']);

// Slow Routes
Route::get('slow_rpc', ['uses' => 'SlowController@rpc']);
Route::get('slow_mysql', ['uses' => 'SlowController@mysql']);

// System Routes
Route::get('system/config', ['uses' => 'SystemController@getConfig']);
Route::post('system/config', ['uses' => 'SystemController@postConfig']);

// Logs Routes
Route::get('logs', ['uses' => 'LogController@logs']);

// Notice
Route::group(['prefix' => 'notice'], function () {
    Route::get('ajax/check', ['uses' => 'NoticeController@ajaxCheck']);
    Route::get('ajax/hasRead', ['uses' => 'NoticeController@hasRead']);
    Route::get('lists', ['uses' => 'NoticeController@lists']);
});