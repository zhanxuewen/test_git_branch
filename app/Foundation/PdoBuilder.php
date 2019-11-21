<?php

namespace App\Foundation;

use PDO;
use Cache;
use Exception;
use Predis\Client;

trait PdoBuilder
{
    protected $env = [];

    protected $_env = [];

    protected $conn_env = [];

    protected $redis = [];

    private function getEnv()
    {
        if (empty($this->env)) $this->env = include base_path() . '/.env.array';
        return $this->env;
    }

    private function getConnEnv()
    {
        if (empty($this->conn_env)) $this->conn_env = include base_path() . '/.conn.conf.php';
        return $this->conn_env;
    }

    /**
     * @param $conn
     * @return Client;
     */
    public function getRedis($conn)
    {
        if (isset($this->redis[$conn])) return $this->redis[$conn];
        $conf = $this->getEnv()['redis'][$conn];
        $option = ['parameters' => ['password' => $conf['password']]];
        $ip = Cache::get('redis_' . $conn . '_ip');
        $hosts = array_merge(is_null($ip) ? [] : [$ip], $conf['host']);
        foreach ($hosts as $k => $host) {
            $this->getMasterRedis($conn, $host, $option);
            if (isset($this->redis[$conn]))
                return $this->redis[$conn];
        }
        return $this->redis[$conn];
    }

    protected function getMasterRedis($conn, $host, $option)
    {
        $redis = new Client($host, $option);
        try {
            $redis->setex('master_redis', 30, $host);
        } catch (Exception $e) {
            return;
        }
        Cache::put('redis_' . $conn . '_ip', $host, 60 * 24 * 7);
        $this->redis[$conn] = $redis;
    }

    public function getConnProjects()
    {
        return array_keys($this->getConnEnv());
    }

    public function getConnections($project)
    {
        return array_keys($this->getConnEnv()[$project]);
    }

    public function getConnDB($project, $conn)
    {
        return $this->getConnEnv()[$project][$conn]['database'];
    }

    public function getConnPdo($project, $conn)
    {
        $conf = $this->getConnEnv()[$project][$conn];
        return $this->newPdo($conf['host'], $conf['database'], $conf['username'], $conf['password']);
    }

    private function newPdo($host, $database, $username, $password)
    {
        try {
            return new PDO("mysql:host={$host};dbname={$database};charset=utf8", $username, $password);
        } catch (Exception $e) {
            return null;
        }
    }

}