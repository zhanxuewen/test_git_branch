<?php

namespace App\Foundation;

use Predis\Client;

trait PdoBuilder
{
    protected $env = [];
    
    private function getEnv()
    {
        if (empty($this->env)) $this->env = include base_path().'/.env.array';
        return $this->env;
    }
    
    protected function getConf($conn)
    {
        return $this->getEnv()[$conn];
    }
    
    public function getRedis($conn)
    {
        $conf = $this->getEnv()['redis'][$conn];
        return new Client($conf);
    }
    
    public function getDbName($conn)
    {
        return $this->getConf($conn)['database'];
    }
    
    public function getPdo($conn, $change_db = null)
    {
        $db = $this->getConf($conn);
        if (!is_null($change_db)) $db['database'] = $change_db;
        return new \PDO("mysql:host=".$db['host'].";dbname=".$db['database'].";charset=utf8", $db['username'], $db['password']);
    }
}