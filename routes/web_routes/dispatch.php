<?php

Route::group(['namespace' => 'Dispatch', 'prefix' => 'dispatch'], function () {
    Route::group(['prefix' => 'dispatcher'], function () {
        Route::group(['prefix' => 'list'], function () {
            Route::get('/', ['uses' => 'DispatcherController@lists']);
        });
        Route::group(['prefix' => 'maps'], function () {
            Route::get('/', ['uses' => 'DispatcherController@maps']);
            Route::get('update', ['uses' => 'DispatcherController@mapsUpdate']);
        });
        Route::group(['prefix' => 'sync'], function () {
            Route::get('items', ['uses' => 'DispatcherController@syncItems']);
        });
    });
});