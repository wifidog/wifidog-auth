<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * auth server error message
     *
     * @example curl 'http://wifidog-auth.lan/gw_message.php?message=denied'
     * @example curl 'http://wifidog-auth.lan/messages/?message=denied'
     * @link http://dev.wifidog.org/wiki/doc/developer/WiFiDogProtocol_V1#GatewayheartbeatingPingProtocol
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->validate($request, [
            'message' => 'required|string|in:denied,activate,failed_validation',
        ]);
        return view('message', [
            'msg' => 'auth.' . $request->input('message'),
        ]);
    }
}
