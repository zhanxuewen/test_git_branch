<?php

Route::group(['namespace' => 'Bank', 'prefix' => 'bank'], function () {
    Route::group(['prefix' => 'learning'], function () {
        Route::get('search/testbank', ['uses' => 'LearningController@searchTestbank']);
        Route::get('sync/entity', ['uses' => 'LearningController@syncEntity']);
    });
    Route::group(['prefix' => 'core'], function () {
        Route::get('resource', ['uses' => 'CoreController@resource']);
    });
    Route::group(['prefix' => 'transmit'], function () {
        Route::get('learning/testbank', ['uses' => 'TransmitController@learningTestbank']);
    });
});