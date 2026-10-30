<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportLegacyKycImages extends Command
{
    protected $signature = 'kyc:import-legacy-images {source : Path to the legacy upload directory}';

    protected $description = 'Copy valid legacy KYC JPEGs into private Laravel storage';

    public function handle(): int
    {
        $source = realpath($this->argument('source'));
        if ($source === false || ! is_dir($source)) {
            $this->error('The source directory does not exist.');

            return self::FAILURE;
        }

        $folders = [
            'nationalid' => 'nationalid',
            'applicationform' => 'applicationform',
            'offerletter' => 'proof-of-residence',
            'studentphotos' => 'studentphoto',
            'biometricpics' => 'biometric',
        ];
        $disk = Storage::disk('kyc');
        $imported = 0;
        $skipped = 0;

        foreach ($folders as $legacyFolder => $category) {
            $directory = $source.DIRECTORY_SEPARATOR.$legacyFolder;
            if (! is_dir($directory)) {
                continue;
            }

            foreach (new \DirectoryIterator($directory) as $file) {
                if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['jpg', 'jpeg'], true)) {
                    continue;
                }

                $regnumber = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $size = $file->getSize();
                $contents = file_get_contents($file->getPathname());
                $imageInfo = $contents === false ? false : getimagesizefromstring($contents);
                if (! preg_match('/^[A-Za-z0-9-]{1,10}$/', $regnumber)
                    || $size === false || $size > 5 * 1024 * 1024
                    || $imageInfo === false || $imageInfo['mime'] !== 'image/jpeg') {
                    $this->warn('Skipped an invalid or unsupported image.');
                    $skipped++;

                    continue;
                }

                $target = $category.'/'.hash('sha256', $regnumber).'.jpg';
                if ($disk->exists($target)) {
                    $this->warn('Skipped an image whose destination already exists.');
                    $skipped++;

                    continue;
                }

                if (! $disk->put($target, $contents)) {
                    $this->error('Failed to copy a legacy image into private storage.');

                    return self::FAILURE;
                }
                $imported++;
            }
        }

        $this->info("Imported {$imported} image(s); skipped {$skipped}.");

        return self::SUCCESS;
    }
}
