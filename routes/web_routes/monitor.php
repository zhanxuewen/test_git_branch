<?php

Route::get('table', ['uses' => 'MonitorController@table']);
Route::get('device', ['uses' => 'MonitorController@device']);
Route::get('order', ['uses' => 'MonitorController@order']);
Route::get('circleTable', ['uses' => 'MonitorController@circleTable']);
Route::get('zabbix', ['uses' => 'MonitorController@zabbix']);
Route::get('schedule', ['uses' => 'MonitorController@schedule']);
Route::get('throttle', ['uses' => 'MonitorController@throttle']);