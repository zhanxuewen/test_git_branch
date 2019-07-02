<?php

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
    Route::post('dispatchRoute', ['uses' => 'AuthorityController@dispatchRoute']);
});