<?php

namespace App\Http\Middleware;

use Closure;
use App\Foundation\PdoBuilder;

class CacheTableRows extends IgnoreRoute
{
    use PdoBuilder;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $redis = $this->getRedis('analyze');
        if ($redis->get('dev_table_rows')) {
            return $next($request);
        }
        $database = $this->getDbName('dev');
        $sql = "SELECT table_name, table_rows FROM information_schema.tables where table_schema='$database'";
        $tables = \DB::setPdo($this->getPdo('dev'))->select($sql);
        $cache = [];
        foreach ($tables as $table) {
            $cache[$table->table_name] = $table->table_rows;
        }
        $redis->setex('dev_table_rows', 60 * 60, json_encode($cache));
        return $next($request);
    }
}
