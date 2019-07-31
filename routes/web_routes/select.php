<?php

Route::group(['namespace' => 'Select', 'prefix' => 'select'], function () {
    Route::get('marketer', ['uses' => 'ShowController@marketer']);
    Route::get('labels', ['uses' => 'ShowController@labels']);

    Route::get('quit_student', ['as' => 'select_quit_student', 'uses' => 'SearchController@quitStudent']);
    Route::get('partner_school', ['uses' => 'SearchController@partnerSchool']);
});