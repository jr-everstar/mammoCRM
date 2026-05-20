<?php

namespace App\Services\TrialAgreements;

use RuntimeException;
use Symfony\Component\Process\Process;

class PdfConverter
{
    public function convert(string $docxPath, string $pdfPath): void
    {
        $binary = $this->binary();

        if ($binary === null) {
            throw new RuntimeException('LibreOffice/soffice is not installed or is not available in PATH.');
        }

        $outputDir = dirname($pdfPath);
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $process = new Process([
            $binary,
            '--headless',
            '--convert-to',
            'pdf',
            '--outdir',
            $outputDir,
            $docxPath,
        ]);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Unable to convert trial agreement to PDF.');
        }

        $convertedPath = $outputDir.'/'.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';
        if ($convertedPath !== $pdfPath && is_file($convertedPath)) {
            rename($convertedPath, $pdfPath);
        }

        if (! is_file($pdfPath)) {
            throw new RuntimeException('PDF conversion finished without creating the expected file.');
        }
    }

    private function binary(): ?string
    {
        foreach (['soffice', 'libreoffice'] as $candidate) {
            $path = trim((string) shell_exec('command -v '.escapeshellarg($candidate).' 2>/dev/null'));

            if ($path !== '') {
                return $path;
            }
        }

        return null;
    }
}
