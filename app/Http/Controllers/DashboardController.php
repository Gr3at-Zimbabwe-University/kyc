<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard');
    }

    public function captureOptions(Request $request): View
    {
        $validated = $request->validate([
            'txtRegNumber2' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9-]+$/'],
        ]);
        $regnumber = $validated['txtRegNumber2'];

        $record = DB::table('tblcbz_student_ids')
            ->where('regnumber', $regnumber)
            ->first([
                'kyc_nat_id',
                'kyc_nat_id_reason',
                'kyc_app_form',
                'kyc_app_form_reason',
            ]);

        return view('kyc.options', [
            'regnumber' => $regnumber,
            'canCaptureNationalId' => $record !== null
                && $record->kyc_nat_id === null
                && $record->kyc_nat_id_reason === null,
            'canCaptureApplicationForm' => $record !== null
                && $record->kyc_app_form === null
                && $record->kyc_app_form_reason === null,
        ]);
    }
}
