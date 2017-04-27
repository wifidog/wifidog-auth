<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    public function redirectTo()
    {
        $uri = '/home';
        if (\Request::has('gw_address') && \Request::has('gw_port')) {
            $uri = 'http://' . \Request::input('gw_address') . ':' . \Request::input('gw_port') . '/wifidog/auth?token=' . \Request::session()->get('_token');
        }
        return $uri;
    }

    public function showLoginForm()
    {
        return view('auth.login', \Request::all());
    }
}
