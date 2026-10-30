<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key1' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9-]+$/'],
        ]);
        $regnumber = $validated['key1'];

        $student = DB::table('tblstudent')
            ->where('regnumber', $regnumber)
            ->first([
                'regnumber',
                'nationalid',
                'surname',
                'forenames',
                'dob3',
                'permanenthomeadd1',
                'permanenthomeadd2',
                'permanenthomeadd3',
                'mobileno',
                'email',
            ]);

        if ($student === null) {
            return $this->failure('Student record not found. Check if Reg Number is valid or if student has created a student portal account.');
        }

        $activeSession = DB::table('tblsession')
            ->where('modules', 'REGISTER')
            ->where('status', true)
            ->max('sessionid');

        $registered = $activeSession !== null && DB::table('tblstudentregistration')
            ->where('regnumber', $regnumber)
            ->where('sessionid', $activeSession)
            ->exists();

        if (! $registered) {
            return $this->failure('Student is not registered for this semester');
        }

        $citizenRecord = DB::table('tblcbz_student_ids')
            ->where('regnumber', $regnumber)
            ->first(['citzn_val', 'citzn_val_reason']);

        if ($citizenRecord === null) {
            return $this->failure('No Auto CITZN VAL Record found for student. Try doing CITZN VAL manually');
        }

        if (! $this->databaseBoolean($citizenRecord->citzn_val)) {
            return $this->failure('Auto CITZN VAL failed because of Reason('.$citizenRecord->citzn_val_reason.'). Review ID Number and try manual CITZN.)');
        }

        return response()->json([
            'key1' => 'SUCCESS',
            'key2' => 'Student details loading',
            'rnum' => $student->regnumber,
            'fnames' => $student->forenames,
            'sname' => $student->surname,
            'national' => $student->nationalid,
            'dob' => $student->dob3,
            'address' => trim(implode(' ', array_filter([
                $student->permanenthomeadd1,
                $student->permanenthomeadd2,
                $student->permanenthomeadd3,
            ], static fn ($value): bool => $value !== null && $value !== ''))),
            'mobile' => $student->mobileno,
            'mail' => $student->email,
        ]);
    }

    public function checkBiometric(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key1' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9-]+$/'],
        ]);
        $regnumber = $validated['key1'];

        $student = DB::table('tblstudent')
            ->where('regnumber', $regnumber)
            ->first(['regnumber', 'surname', 'forenames']);

        if ($student === null) {
            return $this->failure('Student record not found. Check if the Reg Number is correct or if the student has a portal account.');
        }

        $activeSession = DB::table('tblsession')
            ->where('modules', 'REGISTER')
            ->where('status', true)
            ->max('sessionid');
        $registration = $activeSession === null ? null : DB::table('tblstudentregistration')
            ->where('regnumber', $regnumber)
            ->where('sessionid', $activeSession)
            ->first(['programmecode']);

        if ($registration === null) {
            return $this->failure('Student is not registered for the current semester. Please ensure the student has registered before capturing biometrics.');
        }

        $idCollected = DB::table('tblstudentid')
            ->where('regnumber', $regnumber)
            ->where('programmecode', $registration->programmecode)
            ->exists();

        if (! $idCollected) {
            return $this->failure('Student has not yet collected their Student ID for the current session and programme. Please ensure the Student ID has been issued and collected before capturing biometrics.');
        }

        $fileBase = 'biometric/'.hash('sha256', $regnumber);
        $disk = Storage::disk('kyc');
        if ($disk->exists($fileBase.'.jpg') || $disk->exists($fileBase.'.png')) {
            return response()->json([
                'key1' => 'DUPLICATE',
                'key2' => 'A biometric photo has already been submitted for '.$regnumber.'. If you need to replace it, please contact the administrator.',
            ]);
        }

        return response()->json([
            'key1' => 'SUCCESS',
            'key2' => 'Student verified',
            'rnum' => $student->regnumber,
            'fnames' => $student->forenames,
            'sname' => $student->surname,
        ]);
    }

    private function failure(string $message): JsonResponse
    {
        return response()->json([
            'key1' => 'FAILED',
            'key2' => $message,
        ]);
    }

    private function databaseBoolean(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 't' || $value === 'true';
    }
}
