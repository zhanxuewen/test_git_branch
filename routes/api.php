<?php

Route::group([], function () {
    Route::get('audit/get/rules', function () {
        $rules = include storage_path('/') . 'audit_rule.php';
        return json_encode($rules);
    });
});
