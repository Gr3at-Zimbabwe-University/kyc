@extends('layouts.app')

@section('title', 'Dashboard · KYC')

@section('content')
<section class="page-heading">
    <span class="eyebrow">STAFF WORKSPACE</span>
    <h1>Student KYC</h1>
    <p>Look up a registered student, review their details, and continue with document capture.</p>
</section>

<section class="dashboard-grid">
    <article class="panel lookup-panel">
        <div class="section-heading">
            <span class="step-number">01</span>
            <div><h2>Find a student</h2><p>Enter the registration number to load their record.</p></div>
        </div>
        <label for="txtRegNumber">Registration number</label>
        <div class="lookup-row">
            <input type="text" id="txtRegNumber" autocomplete="off" placeholder="e.g. M100000">
            <button type="button" class="button button-primary" id="lookupButton">Search student</button>
        </div>
        <p id="lookupMessage" class="feedback" aria-live="polite"></p>
        <div class="shortcut-card">
            <div class="shortcut-icon">◎</div>
            <div><strong>Biometric photos</strong><span>Capture or upload a student photo.</span></div>
            <a href="{{ route('biometric.capture') }}" class="button button-light">Open</a>
        </div>
    </article>

    <article class="panel student-panel" id="studentPanel" hidden>
        <div class="section-heading">
            <span class="step-number">02</span>
            <div><h2>Student details</h2><p>Review the matched record before continuing.</p></div>
        </div>
        <dl class="student-details">
            <div><dt>Registration</dt><dd id="detailReg"></dd></div>
            <div><dt>First name(s)</dt><dd id="detailFirstNames"></dd></div>
            <div><dt>Surname</dt><dd id="detailSurname"></dd></div>
            <div><dt>National ID</dt><dd id="detailNationalId"></dd></div>
            <div><dt>Date of birth</dt><dd id="detailDob"></dd></div>
            <div><dt>Address</dt><dd id="detailAddress"></dd></div>
            <div><dt>Phone</dt><dd id="detailPhone"></dd></div>
            <div><dt>Email</dt><dd id="detailEmail"></dd></div>
        </dl>
        <form method="post" action="{{ route('capture.options') }}">
            @csrf
            <input type="hidden" name="txtRegNumber2" id="selectedReg">
            <button class="button button-primary button-wide" type="submit">Continue to KYC capture <span aria-hidden="true">→</span></button>
        </form>
    </article>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const input = document.getElementById('txtRegNumber');
    const button = document.getElementById('lookupButton');
    const feedback = document.getElementById('lookupMessage');
    const studentPanel = document.getElementById('studentPanel');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const fields = {
        rnum: 'detailReg',
        fnames: 'detailFirstNames',
        sname: 'detailSurname',
        national: 'detailNationalId',
        dob: 'detailDob',
        address: 'detailAddress',
        mobile: 'detailPhone',
        mail: 'detailEmail'
    };

    async function lookupStudent() {
        feedback.textContent = '';
        studentPanel.hidden = true;
        if (!input.value.trim()) {
            feedback.textContent = 'Please enter a registration number.';
            feedback.className = 'feedback error';
            input.focus();
            return;
        }

        button.disabled = true;
        button.textContent = 'Searching…';
        try {
            const response = await fetch(@json(route('students.check')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ key1: input.value.trim() })
            });
            const result = await response.json();
            if (!response.ok) {
                feedback.textContent = result.errors?.key1?.[0] || 'Unable to search for this student.';
                feedback.className = 'feedback error';
                return;
            }
            if (result.key1 !== 'SUCCESS') {
                feedback.textContent = result.key2;
                feedback.className = 'feedback error';
                return;
            }
            Object.entries(fields).forEach(([key, id]) => {
                document.getElementById(id).textContent = result[key] || '—';
            });
            document.getElementById('selectedReg').value = result.rnum;
            feedback.textContent = 'Student record found.';
            feedback.className = 'feedback success';
            studentPanel.hidden = false;
        } catch {
            feedback.textContent = 'Could not reach the server. Please try again.';
            feedback.className = 'feedback error';
        } finally {
            button.disabled = false;
            button.textContent = 'Search student';
        }
    }

    button.addEventListener('click', lookupStudent);
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter') lookupStudent();
    });
})();
</script>
@endpush