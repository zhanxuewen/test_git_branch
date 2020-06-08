<?php

Route::group(['namespace' => 'Bank', 'prefix' => 'bank'], function () {
    Route::group(['prefix' => 'learning'], function () {
        Route::get('search/testbank', ['uses' => 'LearningController@searchTestbank']);
        Route::get('sync/entity', ['uses' => 'LearningController@syncEntity']);
        Route::get('sync/article', ['uses' => 'LearningController@syncArticle']);
    });
    Route::group(['prefix' => 'core'], function () {
        Route::get('resource', ['uses' => 'CoreController@resource']);
        Route::get('testbankEntity', ['uses' => 'CoreController@updateTestbankEntity']);
    });
    Route::group(['prefix' => 'transmit'], function () {
        Route::get('learning/testbank', ['uses' => 'TransmitController@learningTestbank']);
        Route::get('delete/bill', ['uses' => 'TransmitController@deleteBill']);
    });
});