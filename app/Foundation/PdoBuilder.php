<?php

namespace App\Foundation;

use Predis\Client;

trait PdoBuilder
{
    protected $env = [];

    protected $_env = [];

    protected $conn_env = [];

    protected $redis = [];

    protected $read_redis = [];

    protected $hash = '$2y$10$vdqadK98R4KKaVRB2HzWu.ks5k8LTMc.rCvmBywj2sEdsfMxXz/OG';

    private function getEnv()
    {
        if (empty($this->env)) $this->env = include base_path() . '/.env.array';
        return $this->env;
    }

    private function getSecretEnv()
    {
        is_file(storage_path('app/.env.secret')) or die('No Such File.');
        if (empty($this->_env)) $this->_env = include storage_path('app') . '/.env.secret';
        return $this->_env;
    }

    private function getConnEnv()
    {
        if (empty($this->conn_env)) $this->conn_env = include base_path() . '/.conn.conf.php';
        return $this->conn_env;
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

    public function getReadRedis($conn)
    {
        if (isset($this->read_redis[$conn])) return $this->read_redis[$conn];
        $conf = $this->getEnv()['redis'][$conn];
        $redis = new Client($conf['host'], ['parameters' => ['password' => $conf['password']]]);
        $this->read_redis[$conn] = $redis;
        return $redis;
    }

    /**
     * @param $conn
     * @return \Predis\Client;
     */
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
        return $this->newPdo($db['host'], $db['database'], $db['username'], $db['password']);
    }

    public function getDeveloperConf()
    {
        return $this->getConf('online');
    }

    public function getSecretPdo($conn)
    {
        if (!\Hash::check(env('ONLINE_ALLOW'), $this->hash)) die('Permission Denied!');
        $db = $this->getSecretEnv()[$conn];
        return $this->newPdo($db['host'], $db['database'], $db['username'], $db['password']);
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
            return new \PDO("mysql:host={$host};dbname={$database};charset=utf8", $username, $password);
        } catch (\Exception $e) {
            return null;
        }
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