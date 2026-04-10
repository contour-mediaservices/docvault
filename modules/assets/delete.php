<?php
declare(strict_types=1);

/* =====================================================
   CORE SETUP
===================================================== */
require_once __DIR__ . "/../../config.php";

/* =====================================================
   HELPER
===================================================== */
function dv_cleanup_empty_archive_dirs(string $startDir, string $archiveRoot): void
{
    $archiveRoot = rtrim($archiveRoot, DIRECTORY_SEPARATOR);
    $dir = rtrim($startDir, DIRECTORY_SEPARATOR);

    while ($dir !== '' && $dir !== $archiveRoot && str_starts_with($dir, $archiveRoot)) {
        if (!is_dir($dir)) {
            break;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        if (count($files) !== 0) {
            break;
        }

        @rmdir($dir);
        $dir = dirname($dir);
    }
}

/* =====================================================
   NUR POST ERLAUBT
===================================================== */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /docvault/modules/assets/index.php");
    exit;
}

/* =====================================================
   ID VALIDIEREN
===================================================== */
$id = (int)($_POST["id"] ?? 0);

if ($id <= 0) {
    header("Location: /docvault/modules/assets/index.php");
    exit;
}

/* =====================================================
   ASSET LADEN
===================================================== */
$stmt = $pdo->prepare("
    SELECT dokument_pfad
    FROM assets
    WHERE id = ?
");
$stmt->execute([$id]);

$asset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asset) {
    header("Location: /docvault/modules/assets/index.php");
    exit;
}

$relPath = trim((string)($asset["dokument_pfad"] ?? ""));

/* =====================================================
   BASISPFADE
===================================================== */
$basePath   = realpath(__DIR__ . "/../../");
$archivRoot = realpath($basePath . "/archiv");

if (!$basePath || !$archivRoot) {
    die("Systempfad konnte nicht bestimmt werden.");
}

$filePath      = null;
$fileDir       = null;
$deleteAllowed = false;

if ($relPath !== '') {
    $candidate = $basePath . "/" . ltrim($relPath, "/");

    $normalizedArchivRoot = rtrim(str_replace('\\', '/', $archivRoot), '/') . '/';
    $normalizedCandidate  = str_replace('\\', '/', $candidate);

    if (str_starts_with($normalizedCandidate, $normalizedArchivRoot)) {
        $filePath      = $candidate;
        $fileDir       = dirname($candidate);
        $deleteAllowed = true;
    }
}

/* =====================================================
   TRANSAKTION
===================================================== */
try {

    $pdo->beginTransaction();

    /* Tag-Zuordnungen löschen */
    $pdo->prepare("
        DELETE FROM asset_tag_assignments
        WHERE asset_id = ?
    ")->execute([$id]);

    /* Asset löschen */
    $delete = $pdo->prepare("
        DELETE FROM assets
        WHERE id = ?
    ");
    $delete->execute([$id]);

    if ($delete->rowCount() !== 1) {
        throw new Exception("Datenbankeintrag konnte nicht gelöscht werden.");
    }

    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Löschfehler: " . $e->getMessage());
}

/* =====================================================
   DATEI LÖSCHEN
===================================================== */
if ($deleteAllowed && $filePath !== null && is_file($filePath)) {
    @unlink($filePath);
}

/* =====================================================
   LEERE ORDNER AUFRÄUMEN
===================================================== */
if ($deleteAllowed && $fileDir !== null) {
    dv_cleanup_empty_archive_dirs($fileDir, $archivRoot);
}

/* =====================================================
   REDIRECT
===================================================== */
header("Location: /docvault/modules/assets/index.php?deleted=1");
exit;