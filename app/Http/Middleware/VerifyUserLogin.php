<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class VerifyUserLogin extends IgnoreRoute
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
        if ($this->if_ignore($request))
            return $next($request);
        if (!Auth::check())
            return redirect()->route('login');
        return $next($request);
    }
}
