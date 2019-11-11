<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Foundation\PdoBuilder;

class CacheTableRows extends IgnoreRoute
{
    use PdoBuilder;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $redis = $this->getRedis('analyze');
        foreach (['core-dev', 'core-test', 'learning-dev', 'word_short-dev'] as $conn) {
            if ($redis->get($conn . '_table_rows')) continue;
            list($project, $_conn) = explode('-', $conn);
            $database = $this->getConnDB($project, $_conn);
            $sql = "SELECT table_name, table_rows FROM information_schema.tables where table_schema='$database'";
            $tables = \DB::setPdo($this->getConnPdo($project, $_conn))->select($sql);
            $cache = [];
            foreach ($tables as $table) {
                $cache[$table->table_name] = $table->table_rows;
            }
            $redis->setex($conn . '_table_rows', 60 * 60, json_encode($cache));
        }
        return $next($request);
    }
}
