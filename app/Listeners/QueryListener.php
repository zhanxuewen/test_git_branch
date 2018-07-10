<?php

namespace App\Listeners;

use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Events\Dispatcher;

class QueryListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    
    /**
     * Handle the event.
     *
     * @param Dispatcher $events
     *
     * @return void
     */
    public static function handle(Dispatcher $events)
    {
        $events->listen('illuminate.query', function ($query, $bindings, $time) {
            preg_match('(select|insert|update|delete)', $query, $matches);
            $type   = empty($matches) ? 'unknown' : $matches[0];
            $sql    = vsprintf(str_replace("?", "`%s`", $query), $bindings);
            $insert = "INSERT INTO `sql_log` (`type`, `sql`, `time`, `created_at`) VALUES ('$type','$sql',$time,'".Carbon::now()."')";
            \DB::getPdo()->exec($insert);
        });
    }
}
