<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLegacyAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! filled($request->session()->get('kyc_user.userid'))) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
