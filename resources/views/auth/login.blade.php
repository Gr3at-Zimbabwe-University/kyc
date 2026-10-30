@extends('layouts.app')

@section('title', 'Sign in · KYC')

@section('content')
<section class="login-layout">
    <div class="login-intro">
        <span class="eyebrow">SIMBILIMBI · STUDENT SERVICES</span>
        <h1>Know your student.<br><span>Capture with confidence.</span></h1>
        <p>Capture and verify student KYC documents and Biometrics in one secure place.</p>
        <div class="login-proof"><span class="proof-dot"></span> Staff access · Secure session</div>
    </div>
    <div class="auth-card">
        <div class="auth-mark"><img src="{{ asset('image/logo.png') }}" alt="Institution logo"></div>
        <span class="eyebrow">WELCOME BACK</span>
        <h2>Sign in to KYC</h2>
        <p class="muted">Use your staff login details to continue.</p>
        <form method="post" action="{{ route('login.store') }}" class="form-stack">
            @csrf
            <label for="userid">Login ID</label>
            <input id="userid" name="userid" type="text" value="{{ old('userid') }}" autocomplete="username" required autofocus>
            @error('userid') <p class="field-error">{{ $message }}</p> @enderror
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
            <button type="submit" class="button button-primary">Sign In <span aria-hidden="true">→</span></button>
        </form>
        <p class="auth-note">Your sign-in is protected by a secure session.</p>
    </div>
</section>
@endsection