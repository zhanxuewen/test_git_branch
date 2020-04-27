<?php

Route::group(['namespace' => 'Tool', 'prefix' => 'tool'], function () {
    Route::get('download', ['uses' => 'ToolController@getDownload']);
    Route::post('download', ['uses' => 'ToolController@postDownload']);
    Route::post('ajax/download', ['uses' => 'ToolController@ajaxDownload']);
    Route::get('upload', ['uses' => 'ToolController@getUpload']);
    Route::post('ajax/upload', ['uses' => 'ToolController@ajaxUpload']);

    Route::get('query', ['uses' => 'QueryController@getQuery']);
    Route::post('query', ['uses' => 'QueryController@ajaxQuery']);

});