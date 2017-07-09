<?php
namespace App\Http\Middleware;

use Illuminate\Support\Facades\Log;
use Closure;

class LogAfterRequest
{

    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        Log::info('app.requests ', ['request' => $request->all()]);
    }
}
