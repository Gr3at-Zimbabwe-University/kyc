@extends('layouts.app')

@section('title', 'Capture options · KYC')

@section('content')
<section class="page-heading">
    <span class="eyebrow">REGISTRATION {{ $regnumber }}</span>
    <h1>KYC document capture</h1>
    <p>Choose an available document to capture for this student.</p>
</section>

<section class="panel options-panel">
    @if (! $canCaptureNationalId && ! $canCaptureApplicationForm)
        <div class="empty-state">
            <span class="empty-icon">✓</span>
            <h2>All KYC was captured</h2>
            <p>There are no outstanding document capture options for this student.</p>
        </div>
    @else
        <div class="option-list">
            @if ($canCaptureNationalId)
                <form method="post" action="{{ route('capture.show', ['category' => 'nationalid']) }}" class="option-card">
                    @csrf
                    <input type="hidden" name="txtRegNumber" value="{{ $regnumber }}">
                    <div class="document-icon">ID</div>
                    <div class="option-copy"><strong>National ID</strong><span>Capture the student's national identity document.</span></div>
                    <button type="submit" class="button button-primary">Capture <span aria-hidden="true">→</span></button>
                </form>
            @endif
            @if ($canCaptureApplicationForm)
                <form method="post" action="{{ route('capture.show', ['category' => 'applicationform']) }}" class="option-card">
                    @csrf
                    <input type="hidden" name="txtRegNumber" value="{{ $regnumber }}">
                    <div class="document-icon">AF</div>
                    <div class="option-copy"><strong>Application Form</strong><span>Capture the completed application form.</span></div>
                    <button type="submit" class="button button-primary">Capture <span aria-hidden="true">→</span></button>
                </form>
            @endif
        </div>
    @endif
    <div class="panel-footer">
        <a href="{{ route('dashboard') }}" class="text-link">← Back to dashboard</a>
        <a href="{{ route('biometric.capture') }}" class="text-link">Capture biometric photo →</a>
    </div>
</section>
@endsection