<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Closure;
use http\Client;

class ActivationCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if ($user->status === 0) {
            return $next($request);
        }

        return response()->json([
            'code' => 'auth-002',
            'message' => 'هذا المستخدم محظور '
        ]);
    }
}
