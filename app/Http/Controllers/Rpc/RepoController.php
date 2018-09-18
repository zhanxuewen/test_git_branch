<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;

class RepoController extends Controller
{
    public function getRepositoryList()
    {
        $repos = $this->builder->setModel('repository')->with('functions_list')->get()->toArray();
        return view('rpc.repo_list', compact('repos'));
    }
    
    public function getFunctionInfo($function_id)
    {
        $with     = ['repository', 'setModel', 'apiCall_list.api.service'];
        $function = $this->builder->setModel('functions')->with($with)->find($function_id)->toArray();
        return view('rpc.function_info', compact('function'));
    }
    
    
}