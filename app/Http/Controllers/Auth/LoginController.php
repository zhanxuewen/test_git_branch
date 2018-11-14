<?php

namespace App\Http\Controllers\Auth;

use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function getLogin()
    {
        return view('auth.login');
    }
    
    public function postLogin(Request $request)
    {
        if (!Auth::attempt(['username' => $request->get('username'), 'password' => $request->get('password')], $request->has('remember_me'))) {
            return redirect()->back()->with('message', 'Username or Password is wrong!');
        }
        return redirect()->route('homepage');
    }
    
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
    
}
