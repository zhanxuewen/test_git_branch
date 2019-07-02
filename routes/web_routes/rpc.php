<?php

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