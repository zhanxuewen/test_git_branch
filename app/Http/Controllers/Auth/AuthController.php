<?php

namespace App\Http\Controllers\Auth;

use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.index');
    }
    
    public function edit(Request $request)
    {
        $user    = $this->getUser();
        $new_pwd = $request->get('new_password');
        if (!Auth::attempt(['username' => $user->username, 'password' => $request->get('old_password')])) {
            return redirect()->back()->with('message', 'Old Password is wrong!');
        }
        if ($new_pwd != $request->get('new_password_check')) {
            return redirect()->back()->with('message', 'Check Password is not match!');
        }
        $user->fill(['password' => $new_pwd])->save();
        return redirect()->route('homepage');
    }
    
}
