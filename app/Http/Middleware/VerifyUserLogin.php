<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class VerifyUserLogin
{
    protected $ignore
        = [
            'auth/login',
            'auth/register',
        ];
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $uri = substr(explode('?', $request->getRequestUri())[0], 1);
        if (in_array($uri, $this->ignore))
            return $next($request);
        if (!Auth::check())
            return redirect()->route('login');
        return $next($request);
    }
}
