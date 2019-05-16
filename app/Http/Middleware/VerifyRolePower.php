<?php

namespace App\Http\Middleware;

use App\Foundation\PdoBuilder;
use Auth;
use Closure;
use Luminee\Watchdog\Middleware\GateKeeper;

class VerifyRolePower extends GateKeeper
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
        $id = Auth::user()->id;
        if ($this->check($request, $id)) {
            $redis = $this->getRedis('analyze');
            if (!$redis->get($id . '_routes')) {
                $redis->setex($id . '_routes', 60 * 60 * 24, json_encode($this->getPowersByAccountId($id)));
            }
            return $next($request);
        } else {
            return redirect()->back()->with('message', 'Permission Denied!');
        }
    }

    public function getPowersByAccountId($account_id)
    {
        return \DB::table('watchdog_role_power')
            ->join('watchdog_power', 'watchdog_power.id', '=', 'watchdog_role_power.power_id')
            ->join('watchdog_account_role', 'watchdog_account_role.role_id', '=', 'watchdog_role_power.role_id')
            ->distinct()->where('watchdog_account_role.account_id', $account_id)->pluck('route')->toArray();
    }
}
