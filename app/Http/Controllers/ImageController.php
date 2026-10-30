<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ImageController extends Controller
{
    private const CAPTURE_CATEGORIES = [
        'nationalid' => [
            'title' => 'National ID',
            'column' => 'kyc_nat_id',
            'reason' => 'kyc_nat_id_reason',
        ],
        'applicationform' => [
            'title' => 'Application Form',
            'column' => 'kyc_app_form',
            'reason' => 'kyc_app_form_reason',
        ],
        'proof-of-residence' => [
            'title' => 'Proof of Residence',
            'column' => 'kyc_por',
            'reason' => 'kyc_por_reason',
        ],
    ];

    public function showCapture(Request $request, string $category): View
    {
        $categoryData = $this->captureCategory($category);
        $validated = $request->validate([
            'txtRegNumber' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9-]+$/'],
        ]);

        return view('kyc.capture', [
            'category' => $category,
            'title' => $categoryData['title'],
            'regnumber' => $validated['txtRegNumber'],
        ]);
    }

    public function storeCapture(Request $request, string $category): View
    {
        $categoryData = $this->captureCategory($category);
        $validated = $request->validate([
            'txtRegNumber' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9-]+$/'],
            'image' => ['required', 'string', 'max:8000000'],
        ]);
        $image = $this->decodeImage($validated['image'], ['image/jpeg']);
        $filename = $category.'/'.hash('sha256', $validated['txtRegNumber']).'.jpg';
        $disk = Storage::disk('kyc');

        if (! $disk->put($filename, $image['contents'])) {
            throw new \RuntimeException('The captured document could not be saved.');
        }

        try {
            DB::table('tblcbz_student_ids')
                ->where('regnumber', $validated['txtRegNumber'])
                ->update([
                    $categoryData['column'] => false,
                    $categoryData['reason'] => 'PENDING',
                ]);
        } catch (Throwable $exception) {
            $disk->delete($filename);

            throw $exception;
        }

        return view('kyc.result', [
            'success' => true,
            'title' => $categoryData['title'],
            'message' => $categoryData['title'].' captured successfully.',
            'regnumber' => $validated['txtRegNumber'],
            'retryCategory' => $category,
        ]);
    }

    public function showBiometric(): View
    {
        return view('kyc.biometric');
    }

    public function storeBiometric(Request $request): View
    {
        $validated = $request->validate([
            'txtRegNumber' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9-]+$/'],
            'image' => ['required', 'string', 'max:8000000'],
        ]);
        $image = $this->decodeImage($validated['image'], ['image/jpeg', 'image/png']);
        $extension = $image['mime'] === 'image/png' ? 'png' : 'jpg';
        $fileBase = 'biometric/'.hash('sha256', $validated['txtRegNumber']);
        $disk = Storage::disk('kyc');

        if ($disk->exists($fileBase.'.jpg') || $disk->exists($fileBase.'.png')) {
            return view('kyc.result', [
                'success' => false,
                'title' => 'Biometric Photo',
                'message' => 'A biometric photo has already been submitted for '.$validated['txtRegNumber'].'. If you need to replace it, please contact the administrator.',
                'regnumber' => $validated['txtRegNumber'],
                'retryCategory' => null,
            ]);
        }

        $filename = $fileBase.'.'.$extension;
        if (! $disk->put($filename, $image['contents'])) {
            throw new \RuntimeException('The biometric photo could not be saved.');
        }

        return view('kyc.result', [
            'success' => true,
            'title' => 'Biometric Photo',
            'message' => 'Biometric photo saved successfully for '.$validated['txtRegNumber'].'.',
            'regnumber' => $validated['txtRegNumber'],
            'retryCategory' => null,
        ]);
    }

    public function show(string $category, string $regnumber): Response
    {
        abort_unless(in_array($category, [
            'nationalid',
            'applicationform',
            'proof-of-residence',
            'studentphoto',
            'biometric',
        ], true), 404);
        abort_unless(preg_match('/^[A-Za-z0-9-]{1,10}$/', $regnumber) === 1, 404);

        $disk = Storage::disk('kyc');
        $base = $category.'/'.hash('sha256', $regnumber);
        $path = collect(['jpg', 'png'])
            ->map(static fn (string $extension): string => $base.'.'.$extension)
            ->first(static fn (string $candidate): bool => $disk->exists($candidate));

        abort_if($path === null, 404);

        $mime = pathinfo($path, PATHINFO_EXTENSION) === 'png' ? 'image/png' : 'image/jpeg';

        return response($disk->get($path), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function captureCategory(string $category): array
    {
        abort_unless(isset(self::CAPTURE_CATEGORIES[$category]), 404);

        return self::CAPTURE_CATEGORIES[$category];
    }

    /**
     * @param  list<string>  $allowedMimes
     * @return array{contents: string, mime: string}
     */
    private function decodeImage(string $dataUri, array $allowedMimes): array
    {
        if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,([A-Za-z0-9+\/=\r\n]+)$/', $dataUri, $matches) !== 1) {
            throw ValidationException::withMessages([
                'image' => 'The image data is invalid. Please capture the image again.',
            ]);
        }

        $contents = base64_decode($matches[2], true);
        $imageInfo = $contents === false ? false : getimagesizefromstring($contents);
        if ($contents === false
            || strlen($contents) < 100
            || strlen($contents) > 5 * 1024 * 1024
            || $imageInfo === false
            || ! in_array($imageInfo['mime'], $allowedMimes, true)
            || $matches[1] !== $imageInfo['mime']) {
            throw ValidationException::withMessages([
                'image' => 'The image must be a valid JPEG or PNG no larger than 5 MB.',
            ]);
        }

        return [
            'contents' => $contents,
            'mime' => $imageInfo['mime'],
        ];
    }
}
