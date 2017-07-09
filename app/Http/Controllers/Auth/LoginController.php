<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use JWTAuth;
use Auth;

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
    public function __construct(Request $request)
    {
        $data = [];
        if ($request->has('gw_address') && $request->has('gw_port')) {
            $data = [
                'gw_address' => $request->input('gw_address'),
                'gw_port' => $request->input('gw_port'),
            ];
        }
        if ($request->has('url')) {
            $data['url'] = $request->input('url');
        }
        if (!empty($data)) {
            session($data);
        }
        $this->middleware('guest', ['except' => 'logout']);
    }

    public function redirectTo()
    {
        $uri = '/home';
        $user = Auth::user();
        $token = JWTAuth::fromUser($user);
        if (session('gw_address') && session('gw_port')) {
            $uri = 'http://' . session('gw_address') . ':' . session('gw_port') . '/wifidog/auth?token=' . $token;
        }
        return $uri;
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }
}
