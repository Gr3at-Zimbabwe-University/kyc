<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAuthenticatedLegacyUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (filled($request->session()->get('kyc_user.userid'))) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
