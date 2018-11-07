<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class VerifyRolePower extends IgnoreRoute
{
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
        $route  = implode('|', $router->getMethods()).'@'.$router->getUri();
        $powers = Auth::user()->role[0]->power;
        if ($powers->contains('route', $route)) {
            return $next($request);
        } else {
            return redirect()->back()->with('message', 'Permission Denied!');
        }
    }
}
