<?php

Route::group(['namespace' => 'Auth'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::get('login', ['as' => 'login', 'uses' => 'LoginController@getLogin']);
        Route::post('login', ['uses' => 'LoginController@postLogin']);
        Route::get('register', ['as' => 'register', 'uses' => 'RegisterController@getRegister']);
        Route::post('register', ['uses' => 'RegisterController@postRegister']);
        Route::get('forget/password', ['as' => 'forget_pwd', 'uses' => 'RegisterController@getForgetPassword']);
        Route::post('forget/password', ['uses' => 'RegisterController@postForgetPassword']);
        Route::post('edit', ['uses' => 'AuthController@edit']);
        Route::get('logout', ['as' => 'logout', 'uses' => 'LoginController@logout']);
    });

    Route::get('/', ['as' => 'homepage', 'uses' => 'AuthController@index']);
});