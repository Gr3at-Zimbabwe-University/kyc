@extends('layouts.app')

@section('title', 'Capture '.$title.' · KYC')

@section('content')
<section class="capture-banner">
    <span class="eyebrow">STUDENT DOCUMENT · CAPTURE</span>
    <h1>Capture {{ $title }}</h1>
    <p>Registration number <strong>{{ $regnumber }}</strong></p>
</section>

<section class="capture-grid">
    <article class="panel capture-card">
        <div class="section-heading"><span class="step-number">01</span><div><h2>Camera</h2><p>Position the document clearly in view.</p></div></div>
        <video id="camera" autoplay playsinline></video>
        <p id="cameraError" class="feedback error" aria-live="polite"></p>
        <button type="button" id="takePhoto" class="button button-primary button-wide">Take snapshot</button>
    </article>
    <article class="panel capture-card">
        <div class="section-heading"><span class="step-number">02</span><div><h2>Preview</h2><p>Check the image before submitting.</p></div></div>
        <div id="preview" class="image-preview">Your captured image will appear here.</div>
        <form method="post" action="{{ route('capture.store', ['category' => $category]) }}" id="captureForm">
            @csrf
            <input type="hidden" name="txtRegNumber" value="{{ $regnumber }}">
            <input type="hidden" name="image" id="imageData">
            @error('image') <p class="field-error">{{ $message }}</p> @enderror
            <button type="submit" id="submitCapture" class="button button-primary button-wide" disabled>Submit {{ $title }}</button>
        </form>
    </article>
</section>
<div class="page-actions">
    <a class="text-link" href="{{ route('dashboard') }}">← Home</a>
    <button class="text-link link-button" type="submit" form="optionsForm">← Back to capture options</button>
    <form id="optionsForm" method="post" action="{{ route('capture.options') }}" hidden>@csrf<input type="hidden" name="txtRegNumber2" value="{{ $regnumber }}"></form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const video = document.getElementById('camera');
    const imageData = document.getElementById('imageData');
    const submit = document.getElementById('submitCapture');
    const preview = document.getElementById('preview');
    const takePhoto = document.getElementById('takePhoto');
    let stream;

    navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment', width: { ideal: 900 }, height: { ideal: 700 } }
    }).then(cameraStream => {
        stream = cameraStream;
        video.srcObject = stream;
    }).catch(() => {
        document.getElementById('cameraError').textContent =
            'Camera access is unavailable. Check your browser permission and connection, then try again.';
    });

    takePhoto.addEventListener('click', () => {
        if (!video.videoWidth || !video.videoHeight) {
            document.getElementById('cameraError').textContent = 'The camera is not ready yet. Please wait a moment and try again.';
            return;
        }
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        imageData.value = canvas.toDataURL('image/jpeg', 0.92);
        preview.replaceChildren(Object.assign(document.createElement('img'), {
            src: imageData.value,
            alt: 'Captured {{ strtolower($title) }}'
        }));
        submit.disabled = false;
        takePhoto.textContent = 'Retake snapshot';
    });

    document.getElementById('captureForm').addEventListener('submit', event => {
        if (!imageData.value) {
            event.preventDefault();
            document.getElementById('cameraError').textContent = 'Take a snapshot before submitting.';
        }
    });

    window.addEventListener('pagehide', () => stream?.getTracks().forEach(track => track.stop()));
})();
</script>
@endpush