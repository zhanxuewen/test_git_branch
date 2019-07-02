<?php

Route::get('school', ['uses' => 'SchoolController@school']);
Route::post('school', ['uses' => 'SchoolController@postExport']);

Route::get('student', ['uses' => 'StudentController@student']);
Route::post('student', ['uses' => 'StudentController@postExport']);

Route::get('single', ['uses' => 'SingleController@single']);
Route::post('ajax/single', ['uses' => 'SingleController@ajaxExport']);

Route::get('order/listExcels', ['uses' => 'OrderController@listExcels']);
Route::get('order/exportOrSend', ['uses' => 'OrderController@exportOrSend']);