<?php

Route::group(['namespace' => 'Bank', 'prefix' => 'bank'], function () {
    Route::group(['prefix' => 'learning'], function () {
        Route::get('search/testbank', ['uses' => 'LearningController@searchTestbank']);
        Route::get('sync/entity', ['uses' => 'LearningController@syncEntity']);
    });
});