@extends('layouts.app')

@section('title', $title.' · KYC')

@section('content')
<section class="panel result-panel">
    <div class="result-mark {{ $success ? 'is-success' : 'is-error' }}">{{ $success ? '✓' : '!' }}</div>
    <span class="eyebrow">{{ $success ? 'CAPTURE COMPLETE' : 'NOT COMPLETED' }}</span>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>
    <p class="result-reg">Registration number <strong>{{ $regnumber }}</strong></p>
    <div class="result-actions">
        @if ($retryCategory !== null)
            <form method="post" action="{{ route('capture.options') }}">
                @csrf
                <input type="hidden" name="txtRegNumber2" value="{{ $regnumber }}">
                <button class="button button-primary" type="submit">Back to KYC options</button>
            </form>
            @if (! $success)
                <form method="post" action="{{ route('capture.show', ['category' => $retryCategory]) }}">
                    @csrf
                    <input type="hidden" name="txtRegNumber" value="{{ $regnumber }}">
                    <button class="button button-light" type="submit">Try again</button>
                </form>
            @endif
        @else
            <a href="{{ route('biometric.capture') }}" class="button button-primary">Capture another</a>
        @endif
        <a href="{{ route('dashboard') }}" class="text-link">Home</a>
    </div>
</section>
@endsection
