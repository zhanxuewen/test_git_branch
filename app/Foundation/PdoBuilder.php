<?php

namespace App\Foundation;

use Predis\Client;

trait PdoBuilder
{
    protected $env = [];

    protected $redis = [];

    private function getEnv()
    {
        if (empty($this->env)) $this->env = include base_path() . '/.env.array';
        return $this->env;
    }

    private function modifyEnv($keys, $value)
    {
        if (empty($this->env)) return 'Env is empty.';
        $tmp = &$this->env;
        foreach (explode('.', $keys) as $key) {
            $tmp = &$tmp[$key];
        }
        $tmp = $value;
        return true;
    }

    private function modifyEnvFile()
    {
        $file = fopen(base_path('.env.array'), 'w+');
        $txt = "<?php\r\n\r\nreturn [\r\n" . $this->arrayToContent($this->env, 0) . "\r\n\r\n];";
        fwrite($file, $txt);
        fclose($file);
    }

    private function arrayToContent($array, $i)
    {
        $txt = '';
        $blank = $this->getBlank($i);
        foreach ($array as $key => $value) {
            $txt .= "\r\n$blank" . (is_numeric($key) ? '' : "'$key' => ");
            $txt .= is_array($value) ? "[\r\n" . $this->arrayToContent($value, $i + 1) . "\r\n\r\n$blank]," : "'$value',";
        }
        return $txt;
    }

    protected function getConf($conn)
    {
        return $this->getEnv()[$conn];
    }

    public function getRedis($conn)
    {
        if (isset($this->redis[$conn])) return $this->redis[$conn];
        $conf = $this->getEnv()['redis']['cluster'][$conn];
        $option = ['parameters' => ['password' => $conf['password']]];
        foreach ($conf['host'] as $k => $host) {
            $this->getMasterRedis($conn, $host, $option);
            if (!isset($this->redis[$conn])) continue;
            $this->bubbleItem($conf['host'], $k, $host);
            break;
        }
        $this->modifyEnv("redis.cluster.$conn.host", $conf['host']);
        $this->modifyEnvFile();
        return $this->redis[$conn];
    }

    protected function getMasterRedis($conn, $host, $option)
    {
        $redis = new Client($host, $option);
        try {
            $redis->setex('master_redis', 30, $host);
        } catch (\Exception $e) {
            return;
        }
        $this->redis[$conn] = $redis;
    }

    public function getDbName($conn)
    {
        return $this->getConf($conn)['database'];
    }

    public function getPdo($conn, $change_db = null)
    {
        $db = $this->getConf($conn);
        if (!is_null($change_db)) $db['database'] = $change_db;
        return new \PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['database'] . ";charset=utf8", $db['username'], $db['password']);
    }

    private function bubbleItem(&$array, $key, $value)
    {
        if ($key == 0) return;
        unset($array[$key]);
        array_unshift($array, $value);
    }

    private function getBlank($i)
    {
        $blank = '';
        for ($j = 0; $j <= $i; $j++) {
            $blank .= '    ';
        }
        return $blank;
    }
}