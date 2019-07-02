<?php

Route::group(['namespace' => 'Auth'], function () {
    include base_path('routes/web_routes/') . 'auth.php';
});

Route::group(['namespace' => 'User', 'prefix' => 'user'], function () {
    include base_path('routes/web_routes/') . 'user.php';
});

Route::group(['namespace' => 'Export', 'prefix' => 'export'], function () {
    include base_path('routes/web_routes/') . 'export.php';
});

Route::group(['prefix' => 'monitor'], function () {
    include base_path('routes/web_routes/') . 'monitor.php';
});

Route::group(['middleware' => 'cache.rows'], function () {
    include base_path('routes/web_routes/') . 'sql.php';
});

Route::group(['namespace' => 'Select', 'prefix' => 'select'], function () {
    include base_path('routes/web_routes/') . 'select.php';
});

Route::group(['namespace' => 'Database', 'prefix' => 'database'], function () {
    include base_path('routes/web_routes/') . 'database.php';
});

Route::group(['namespace' => 'Rpc'], function () {
    include base_path('routes/web_routes/') . 'rpc.php';
});

Route::group([], function () {
    include base_path('routes/web_routes/') . 'tool.php';
});

Route::get('dashboard', ['as' => 'dashboard', 'uses' => 'DashboardController@dashboard']);

Route::get('diagrams/uml', ['uses' => 'DiagramController@uml']);

// Slow Routes
Route::get('slow_rpc', ['uses' => 'SlowController@rpc']);
Route::get('slow_mysql', ['uses' => 'SlowController@mysql']);

// Tool Routes


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