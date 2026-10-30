<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'KYC'))</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kyc.css') }}">
</head>
<body>
<div class="site-shell">
    <header class="site-header">
        <a class="brand" href="{{ session()->has('kyc_user.userid') ? route('dashboard') : route('login') }}">
            <img src="{{ asset('image/logo.png') }}" alt="">
            <span><strong>KYC</strong><small>KYC Services</small></span>
        </a>
        @if (session()->has('kyc_user.userid'))
            <nav class="site-nav" aria-label="Main navigation">
                <a href="{{ route('dashboard') }}">Home</a>
                <a href="{{ route('biometric.capture') }}">Biometric Photos</a>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-logout">Sign Out</button>
                </form>
            </nav>
        @endif
    </header>

    <main>
        @yield('content')
    </main>
    <footer class="site-footer">· KYC ·</footer>
</div>
@stack('scripts')
</body>
</html>