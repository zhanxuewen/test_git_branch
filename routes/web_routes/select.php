<?php

Route::group(['namespace' => 'Select', 'prefix' => 'select'], function () {
    Route::get('marketer', ['uses' => 'ShowController@marketer']);
    Route::get('labels', ['uses' => 'ShowController@labels']);
    Route::get('feedback', ['uses' => 'ShowController@feedback']);
    Route::get('abnormal', ['uses' => 'ShowController@abnormal']);

    Route::get('wordbank', ['uses' => 'SearchController@wordbank']);
    Route::get('quit_student', ['as' => 'select_quit_student', 'uses' => 'SearchController@quitStudent']);
    Route::get('yellow_account', ['uses' => 'SearchController@yellowAccount']);
    Route::get('partner_school', ['uses' => 'SearchController@partnerSchool']);
});