<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    protected $env = [];
    
    protected function getEnv()
    {
        if (empty($this->env)) $this->env = include_once base_path().'/.env.array';
        return $this->env;
    }
    
    protected function getPdo($conn)
    {
        $db = $this->getEnv()[$conn];
        return new \PDO("mysql:host=".$db['host'].";dbname=".$db['database'], $db['username'], $db['password']);
    }
}
