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

    public function flushCache()
    {
        $this->delUserCache(Auth::user()->id);
        return redirect()->back();
    }

    public function edit(Request $request)
    {
        $user = $this->getUser();
        switch ($request->get('type', 'password')) {
            case 'password':
                if (!is_null($message = $this->editPassword($user, $request))) {
                    return redirect()->back()->with('message', $message);
                }
                break;
            case 'avatar':
                $user->fill(['avatar' => $request->get('avatar')])->save();
                $this->delUserCache($user->id);
                break;
        }
        return redirect()->route('homepage');

    }

    protected function editPassword($user, Request $request)
    {
        $new_pwd = $request->get('new_password');
        if (!Auth::attempt(['username' => $user->username, 'password' => $request->get('old_password')])) {
            return 'Old Password is wrong!';
        }
        if ($new_pwd != $request->get('new_password_check')) {
            return 'Check Password is not match!';
        }
        $user->fill(['password' => $new_pwd])->save();
        return null;
    }

}
