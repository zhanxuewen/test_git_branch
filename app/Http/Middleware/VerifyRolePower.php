<?php

namespace App\Http\Middleware;

use App\Foundation\PdoBuilder;
use Auth;
use Closure;

class VerifyRolePower extends IgnoreRoute
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
        if ($this->if_ignore($request)) {
            return $next($request);
        }
        $router = \Route::getRoutes()->match($request);
        $route  = implode('|', $router->methods()).'@'.$router->uri();
        $powers = Auth::user()->role[0]->power;
        $id     = Auth::user()->id;
        $redis  = $this->getRedis('analyze');
        if (!$redis->get($id.'_routes')) {
            $redis->setex($id.'_routes', 60 * 60 * 24, json_encode($powers->pluck('route')->toArray()));
        }
        if ($powers->contains('route', $route)) {
            return $next($request);
        } else {
            return redirect()->back()->with('message', 'Permission Denied!');
        }
    }
}
