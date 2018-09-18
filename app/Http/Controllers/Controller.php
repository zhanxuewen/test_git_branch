<?php

namespace App\Http\Controllers;

use Predis\Client;
use App\Helper\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    protected $env = [];
    
    protected $builder;
    
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }
    
    private function getEnv()
    {
        if (empty($this->env)) $this->env = include_once base_path().'/.env.array';
        return $this->env;
    }
    
    protected function getConf($conn)
    {
        return $this->getEnv()[$conn];
    }
    
    protected function getRedis($conn)
    {
        $conf = $this->getEnv()['redis'][$conn];
        return new Client($conf);
    }
    
    protected function getStorage()
    {
        return Storage::disk('local');
    }
    
    protected function checkFile($file)
    {
        $storage = $this->getStorage();
        if (!$storage->exists($file)) $storage->put($file, '');
        return $storage;
    }
    
    protected function getFile($file)
    {
        return $this->checkFile($file)->get($file);
    }
    
    protected function appendContent($file, $content)
    {
        return $this->checkFile($file)->append($file, $content);
    }
    
    protected function getDbName($conn)
    {
        return $this->getConf($conn)['database'];
    }
    
    protected function getPdo($conn, $is_word_pk = false)
    {
        $db = $this->getConf($conn);
        if ($is_word_pk) $db['database'] = 'wordpk';
        return new \PDO("mysql:host=".$db['host'].";dbname=".$db['database'], $db['username'], $db['password']);
    }
    
    protected function buildSql($query, $param)
    {
        return $this->$query($param);
    }
    
    protected function getZabbixToken()
    {
        return 'Cookie:zbx_sessionid=6b594637293a09024ff0c881f59d64c0';
    }
    
    protected function getManageToken()
    {
        return 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjMzNDksImlzcyI6Imh0dHA6Ly9hcGkubWFuYWdlLnd4enh6ai5jb20vYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE1MzYxNTE0NDUsImV4cCI6MTUzNzM2MTA0NSwibmJmIjoxNTM2MTUxNDQ1LCJqdGkiOiJoYWwyNWdSVXRYcnZNMnVyIn0.JU-mq63ncFcg4nz_1HJYAI8zobOb32yOGeeDaDPYcs0';
    }
    
    protected function response($code, $message = '')
    {
        return ['code' => $code, 'message' => $message];
    }
    
}
