<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 0);

$basePath       = '/volume1/web/docvault/';
$scanDir        = $basePath . 'scan/';
$processingDir  = $basePath . 'processing/';
$archiveBaseDir = $basePath . 'archiv/';
$logFile        = $basePath . 'logs/scan.log';
$lockFile       = $basePath . 'process.lock';

$dockerBinary = '/usr/local/bin/docker';
$dockerPoppler   = 'minidocks/poppler';
$dockerTesseract = 'tesseractshadow/tesseract4re';

require_once '/volume1/web/docvault/config.php';

/* LOCK */
if (file_exists($lockFile)) exit;
file_put_contents($lockFile, (string)time());

register_shutdown_function(function() use ($lockFile) {
    if (file_exists($lockFile)) unlink($lockFile);
});

/* LOG */
function logMessage(string $message): void
{
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

/* DATUM */
function extractDocumentDate(string $text): ?string
{
    if (preg_match('/\b(\d{2})[.\-](\d{2})[.\-](\d{4})\b/', $text, $m)) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $text, $m)) {
        return "{$m[1]}-{$m[2]}-{$m[3]}";
    }
    return null;
}

/* TEXT CLEANING */
function cleanOcrText(string $text): string
{
    $lines = explode("\n", $text);
    $clean = [];

    foreach ($lines as $line) {

        $line = trim($line);
        if ($line === '') continue;

        if (
            str_contains($line, 'Pulling') ||
            str_contains($line, 'Downloaded') ||
            str_contains($line, 'Digest:') ||
            str_contains($line, 'Status:') ||
            str_contains($line, 'Unable to find image') ||
            str_contains($line, 'Verifying') ||
            str_contains($line, 'Pull complete')
        ) {
            continue;
        }

        $clean[] = $line;
    }

    $text = implode("\n", $clean);

    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n{2,}/', "\n\n", $text);
    $text = preg_replace('/\b([A-ZÄÖÜ])\s(?=[A-ZÄÖÜ]\b)/u', '$1', $text);
    $text = preg_replace('/\s+([.,:;])/u', '$1', $text);

    return trim($text);
}

/* OCR: PDF TEXT */
function runPdfToText(string $dockerBinary, string $basePath, string $file): string
{
    global $dockerPoppler;

    $relativePath = str_replace($basePath, '/data/', $file);

    $cmd = "$dockerBinary run --rm -v {$basePath}:/data $dockerPoppler pdftotext " .
        escapeshellarg($relativePath) . " -";

    return shell_exec($cmd . ' 2>&1') ?? '';
}

/* OCR: TESSERACT */
function runTesseract(string $dockerBinary, string $basePath, string $file): string
{
    global $dockerPoppler, $dockerTesseract;

    $relativePath = str_replace($basePath, '/data/', $file);
    $tmpPrefix    = $basePath . 'tmp_ocr_' . uniqid();

    logMessage("Starte OCR Fallback");

    // PDF → PNG
    $cmd1 = "$dockerBinary run --rm -v {$basePath}:/data $dockerPoppler pdftoppm -png " .
        escapeshellarg($relativePath) . " " . escapeshellarg('/data/' . basename($tmpPrefix));

    shell_exec($cmd1 . ' 2>&1');

    $images = glob($tmpPrefix . '*');

    if (!$images) {
        logMessage("FEHLER: Keine PNG erzeugt");
        return '';
    }

    $fullText = '';

    foreach ($images as $img) {

        $imgRel = str_replace($basePath, '/data/', $img);

        $cmd2 = "$dockerBinary run --rm -v {$basePath}:/data $dockerTesseract tesseract " .
            escapeshellarg($imgRel) . " stdout -l deu+eng";

        $out = shell_exec($cmd2 . ' 2>&1') ?? '';

        // CLEAN DOCKER NOISE
        $filtered = [];
        foreach (explode("\n", $out) as $line) {
            if (
                str_contains($line, 'Pulling') ||
                str_contains($line, 'Downloaded') ||
                str_contains($line, 'Digest:') ||
                str_contains($line, 'Status:') ||
                str_contains($line, 'Unable to find image') ||
                str_contains($line, 'Verifying') ||
                str_contains($line, 'Pull complete')
            ) continue;

            $filtered[] = $line;
        }

        $fullText .= "\n" . implode("\n", $filtered);

        unlink($img);
    }

    return trim($fullText);
}

/* MAIN */
try {

    logMessage("Script gestartet");

    $pdo = new PDO(
        "mysql:unix_socket=/run/mysqld/mysqld10.sock;dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $files = array_merge(
        glob($processingDir . '*.{pdf,PDF}', GLOB_BRACE) ?: [],
        glob($scanDir . '*.{pdf,PDF}', GLOB_BRACE) ?: []
    );

    if (!$files) {
        logMessage("Keine Dateien vorhanden");
        exit;
    }

    foreach ($files as $file) {

        $basename = basename($file);

        if (str_starts_with($file, $scanDir)) {
            $processingFile = $processingDir . $basename;
            if (!rename($file, $processingFile)) {
                logMessage("FEHLER: Verschieben fehlgeschlagen");
                continue;
            }
        } else {
            $processingFile = $file;
        }

        logMessage("Verarbeite: $basename");

        $hash = hash_file('sha256', $processingFile);
        $check = $pdo->prepare("SELECT id FROM assets WHERE filehash = ?");
        $check->execute([$hash]);

        if ($check->fetch()) {
            logMessage("Dublette erkannt");
            unlink($processingFile);
            continue;
        }

        $text = runPdfToText($dockerBinary, $basePath, $processingFile);
        $text = trim($text);

        if (strlen(preg_replace('/\s+/', '', $text)) > 20) {
            logMessage("pdftotext OK");
        } else {
            logMessage("pdftotext unbrauchbar → Tesseract");
            $text = runTesseract($dockerBinary, $basePath, $processingFile);
        }

        $text = cleanOcrText($text);

        if ($text === '') {
            $text = '[KEIN OCR TEXT ERKANNT]';
        }

        $documentDate = extractDocumentDate($text) ?? date('Y-m-d');
        $documentYear = date('Y', strtotime($documentDate));

        $categoryId   = 8;
        $categoryName = 'Unkategorisiert';
        $safeCat      = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $categoryName);

        $archiveDir = $archiveBaseDir . $documentYear . '/' . $safeCat . '/';

        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0775, true);
            chown($archiveDir, 'http');
            chgrp($archiveDir, 'users');
        }

        $finalPath = $archiveDir . $basename;

        if (!rename($processingFile, $finalPath)) {
            logMessage("FEHLER: Archivieren fehlgeschlagen");
            continue;
        }

        $relativeDbPath = str_replace($basePath, '', $finalPath);

        $insert = $pdo->prepare("
            INSERT INTO assets
            (name, category_id, subcategory_id, year, notes, dokument_pfad, status, filehash, erstellt_am)
            VALUES (?, ?, NULL, ?, ?, ?, 'neu', ?, NOW())
        ");

        $insert->execute([
            $basename,
            $categoryId,
            $documentYear,
            $text,
            $relativeDbPath,
            $hash
        ]);

        logMessage("Fertig verarbeitet: $basename");
    }

} catch (Throwable $e) {
    logMessage("SYSTEMFEHLER: " . $e->getMessage());
}