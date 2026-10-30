<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'userid' => ['required', 'string', 'max:10'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = DB::table('tblusers')
            ->where('userid', $validated['userid'])
            ->first(['userid', 'userpassword']);

        if ($user === null) {
            throw ValidationException::withMessages([
                'userid' => 'Invalid Login ID or Password.',
            ]);
        }

        if (! is_string($user->userpassword)
            || ! hash_equals($user->userpassword, $validated['password'])) {
            throw ValidationException::withMessages([
                'userid' => 'Invalid Login ID or Password.',
            ]);
        }

        $sessionId = DB::table('tblsession')
            ->where('modules', 'REGISTRY')
            ->where('status', true)
            ->value('sessionid');

        DB::transaction(function () use ($validated): void {
            DB::table('tblaudittrail')->insert([
                'module' => 'KYC',
                'platform' => 'KYC USER',
                'username' => $validated['userid'],
                'usertype' => 'STAFF',
                'trandescription' => 'LOGIN SUCCESS WITH USERID '.$validated['userid'],
            ]);
        });

        $request->session()->regenerate();
        $request->session()->put([
            'kyc_user' => [
                'userid' => $user->userid,
            ],
            'SESS' => $sessionId,
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
