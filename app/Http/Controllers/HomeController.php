<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use Auth;
use Meta;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $token = JWTAuth::fromUser($user);
        $data = [
            'msg' => 'auth.logged_in',
        ];
        if (session('gw_address') && session('gw_port')) {
            $data['wifidog_uri'] = 'http://' . session('gw_address') . ':' . session('gw_port') . '/wifidog/auth?token=' . $token;
            Meta::set('wifidog-token', $token);
        }
        return view('home', $data);
    }
}
