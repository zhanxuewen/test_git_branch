<?php

namespace App\Http\Controllers\Api;

use App\Helper\Builder;

abstract class BaseApi
{
    protected $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    protected function success($data)
    {
        return response()->json(['code' => 200, 'data' => $data]);
    }
}