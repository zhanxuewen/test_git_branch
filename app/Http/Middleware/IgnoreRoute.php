<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class IgnoreRoute
{
    protected $ignore
        = [
            'auth/login',
            'auth/register',
            'auth/forget/password'
        ];
    
    /**
     * If in ignore array
     *
     * @param  \Illuminate\Http\Request $request
     * @return boolean
     */
    protected function if_ignore($request)
    {
        $uri = substr(explode('?', $request->getRequestUri())[0], 1);
        return in_array($uri, $this->ignore);
    }
}
