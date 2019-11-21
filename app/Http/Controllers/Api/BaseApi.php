<?php

namespace App\Http\Controllers\Api;

abstract class BaseApi
{
    protected $modelMap = [];

    public function __construct()
    {
        $this->modelMap = include app_path('Models') . '/_models.php';
    }

    /**
     * @param string $model
     * @return mixed
     */
    protected function setModel($model)
    {
        return new $this->modelMap[$model];
    }

    protected function success($data)
    {
        return response()->json(['code' => 200, 'data' => $data]);
    }
}