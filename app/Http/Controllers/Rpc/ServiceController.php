<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;

class ServiceController extends Controller
{
    public function getServiceList()
    {
        $services = $this->builder->setModel('service')->with('api_list')->get()->toArray();
        return view('rpc.service_list', compact('services'));
    }
    
    public function getApiInfo($api_id)
    {
        $api   = $this->builder->setModel('api')->with(['call_list.repository', 'service'])->find($api_id)->toArray();
        $vars  = explode(',', $api['service']['ioc_variables']);
        $repos = explode(',', $api['service']['ioc_repos']);
        $map   = array_combine($repos, $vars);
        return view('rpc.api_info', compact('api', 'map'));
    }
    
}