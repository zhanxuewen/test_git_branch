<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;

class DBController extends Controller
{
    public function getModelList()
    {
        $modules = $this->builder->setModel('module')->withRelatedOrderBy('model_list', 'table')->get()->toArray();
        return view('rpc.model_list', compact('modules'));
    }
    
    public function getModelInfo($model_id)
    {
        $model = $this->builder->setModel('model')->with(['relation_list', 'repo_function_list'])->find($model_id)->toArray();
        return view('rpc.model_info', compact('model'));
    }
}