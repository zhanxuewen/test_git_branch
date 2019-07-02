<?php

Route::group([], function () {
    Route::get('tool/download', ['uses' => 'ToolController@getDownload']);
    Route::post('tool/download', ['uses' => 'ToolController@postDownload']);
    Route::post('ajax/tool/download', ['uses' => 'ToolController@ajaxDownload']);
    Route::get('tool/upload', ['uses' => 'ToolController@getUpload']);
    Route::post('ajax/tool/upload', ['uses' => 'ToolController@ajaxUpload']);
});