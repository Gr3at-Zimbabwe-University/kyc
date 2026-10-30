@extends('layouts.app')

@section('title', 'Biometric photo · KYC')

@section('content')
<section class="page-heading">
    <span class="eyebrow">BIOMETRIC PHOTO</span>
    <h1>Capture student photo</h1>
    <p>Verify the student's current registration and ID collection before taking their photo.</p>
</section>
<section class="panel biometric-panel">
    <div id="lookupSection">
        <label for="biometricReg">Registration number</label>
        <div class="lookup-row">
            <input id="biometricReg" type="text" placeholder="e.g. M100000" autocomplete="off">
            <button type="button" class="button button-primary" id="verifyButton">Verify student</button>
        </div>
        <p id="biometricMessage" class="feedback" aria-live="polite"></p>
    </div>

    <div id="biometricCapture" hidden>
        <div class="verified-student">
            <div class="proof-dot"></div>
            <div><strong id="verifiedName"></strong><span id="verifiedReg"></span></div>
            <span class="verified-badge">Verified</span>
        </div>
        <div class="capture-tabs">
            <button type="button" class="tab-button active" data-tab="camera">Use camera</button>
            <button type="button" class="tab-button" data-tab="upload">Upload photo</button>
        </div>
        <form method="post" action="{{ route('biometric.store') }}" id="biometricForm">
            @csrf
            <input type="hidden" name="txtRegNumber" id="biometricRegHidden">
            <input type="hidden" name="image" id="biometricImage">
            <div id="cameraTab" class="biometric-tab">
                <div class="biometric-preview-grid">
                    <div><video id="biometricCamera" autoplay playsinline></video><button type="button" class="button button-primary button-wide" id="takeBiometric">Take photo</button></div>
                    <div id="cameraPreview" class="image-preview">Your photo preview will appear here.</div>
                </div>
            </div>
            <div id="uploadTab" class="biometric-tab" hidden>
                <label for="photoFile">Select an image file</label>
                <input type="file" id="photoFile" accept="image/jpeg,image/png">
                <div id="filePreview" class="image-preview">Your photo preview will appear here.</div>
            </div>
            @error('image') <p class="field-error">{{ $message }}</p> @enderror
            <button type="submit" class="button button-primary button-wide" id="submitBiometric" disabled>Submit biometric photo</button>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const verifyButton = document.getElementById('verifyButton');
    const regInput = document.getElementById('biometricReg');
    const message = document.getElementById('biometricMessage');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const capture = document.getElementById('biometricCapture');
    const hiddenReg = document.getElementById('biometricRegHidden');
    const hiddenImage = document.getElementById('biometricImage');
    const submit = document.getElementById('submitBiometric');
    const cameraVideo = document.getElementById('biometricCamera');
    let cameraStream;

    async function verifyStudent() {
        message.textContent = '';
        capture.hidden = true;
        if (!regInput.value.trim()) {
            message.textContent = 'Please enter a registration number.';
            message.className = 'feedback error';
            return;
        }
        verifyButton.disabled = true;
        verifyButton.textContent = 'Verifying…';
        try {
            const response = await fetch(@json(route('students.biometric-check')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ key1: regInput.value.trim() })
            });
            const result = await response.json();
            if (!response.ok || result.key1 !== 'SUCCESS') {
                message.textContent = result.errors?.key1?.[0] || result.key2 || 'Unable to verify this student.';
                message.className = 'feedback error';
                return;
            }
            hiddenReg.value = result.rnum;
            document.getElementById('verifiedReg').textContent = result.rnum;
            document.getElementById('verifiedName').textContent = `${result.fnames} ${result.sname}`;
            capture.hidden = false;
            message.textContent = '';
            startCamera();
        } catch {
            message.textContent = 'Could not reach the server. Please try again.';
            message.className = 'feedback error';
        } finally {
            verifyButton.disabled = false;
            verifyButton.textContent = 'Verify student';
        }
    }

    async function startCamera() {
        if (!navigator.mediaDevices?.getUserMedia) return;
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 600 }, height: { ideal: 480 } }
            });
            cameraVideo.srcObject = cameraStream;
        } catch {
            const error = document.createElement('p');
            error.className = 'feedback error';
            error.textContent = 'Camera access is unavailable. Use the upload option instead.';
            document.getElementById('cameraTab').prepend(error);
        }
    }

    function showPreview(container, dataUri, alt) {
        const image = document.createElement('img');
        image.src = dataUri;
        image.alt = alt;
        container.replaceChildren(image);
        hiddenImage.value = dataUri;
        submit.disabled = false;
    }

    verifyButton.addEventListener('click', verifyStudent);
    regInput.addEventListener('keydown', event => {
        if (event.key === 'Enter') verifyStudent();
    });
    document.getElementById('takeBiometric').addEventListener('click', () => {
        if (!cameraVideo.videoWidth) {
            message.textContent = 'The camera is not ready. Please wait or upload a photo.';
            message.className = 'feedback error';
            return;
        }
        const canvas = document.createElement('canvas');
        canvas.width = cameraVideo.videoWidth;
        canvas.height = cameraVideo.videoHeight;
        canvas.getContext('2d').drawImage(cameraVideo, 0, 0);
        showPreview(document.getElementById('cameraPreview'), canvas.toDataURL('image/jpeg', 0.92), 'Captured biometric photo');
    });
    document.getElementById('photoFile').addEventListener('change', event => {
        const file = event.target.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            message.textContent = 'Choose a JPEG or PNG image no larger than 5 MB.';
            message.className = 'feedback error';
            event.target.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = () => showPreview(document.getElementById('filePreview'), reader.result, 'Selected biometric photo');
        reader.readAsDataURL(file);
    });
    document.querySelectorAll('.tab-button').forEach(button => button.addEventListener('click', () => {
        document.querySelectorAll('.tab-button').forEach(tab => tab.classList.toggle('active', tab === button));
        document.getElementById('cameraTab').hidden = button.dataset.tab !== 'camera';
        document.getElementById('uploadTab').hidden = button.dataset.tab !== 'upload';
        hiddenImage.value = '';
        submit.disabled = true;
    }));
    document.getElementById('biometricForm').addEventListener('submit', event => {
        if (!hiddenImage.value) {
            event.preventDefault();
            message.textContent = 'Capture or select a photo before submitting.';
            message.className = 'feedback error';
        }
    });
    window.addEventListener('pagehide', () => cameraStream?.getTracks().forEach(track => track.stop()));
})();
</script>
@endpush
