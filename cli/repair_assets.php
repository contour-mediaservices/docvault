<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

/* =====================================================
   LOCK (für Monitor Auto-Repair)
===================================================== */
$lockFile = '/volume1/web/docvault/repair.lock';

register_shutdown_function(function() use ($lockFile) {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
});

/* =====================================================
   BASIS
===================================================== */
$basePath       = '/volume1/web/docvault/';
$archiveBaseDir = $basePath . 'archiv/';

echo "=== ASSET REPAIR START ===\n";

/* =====================================================
   OPTIONAL: EINZEL-ASSET
===================================================== */
$singleId = $_POST['asset_id'] ?? null;

/* =====================================================
   HELPER
===================================================== */
function dv_sanitize_path_part(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/[^\p{L}\p{N}\-_ ]/u', '_', $value);
    $value = preg_replace('/\s+/u', '_', $value);
    $value = preg_replace('/_+/u', '_', $value);
    $value = trim((string)$value, '_');

    return $value !== '' ? $value : 'Unbekannt';
}

function dv_unique_target_path(string $targetPath): string
{
    if (!file_exists($targetPath)) {
        return $targetPath;
    }

    $info = pathinfo($targetPath);
    $dir  = $info['dirname'];
    $name = $info['filename'];
    $ext  = isset($info['extension']) ? '.' . $info['extension'] : '';

    $i = 1;
    do {
        $candidate = $dir . '/' . $name . '_' . $i . $ext;
        $i++;
    } while (file_exists($candidate));

    return $candidate;
}

/* =====================================================
   QUERY
===================================================== */
$sql = "
    SELECT a.*, c.name AS category_name, s.name AS subcategory_name
    FROM assets a
    LEFT JOIN categories c ON c.id = a.category_id
    LEFT JOIN subcategories s ON s.id = a.subcategory_id
";

if ($singleId) {
    $sql .= " WHERE a.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$singleId]);
} else {
    $stmt = $pdo->query($sql);
}

$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   LOOP
===================================================== */
foreach ($assets as $a) {

    $id   = (int)$a["id"];
    $path = trim((string)($a["dokument_pfad"] ?? ''));

    if ($path === '') {
        echo "[ID $id] KEIN PFAD\n";
        continue;
    }

    $fullPath = $basePath . $path;

    /* =================================================
       DATEI EXISTIERT NICHT → SUCHEN
    ================================================= */
    if (!file_exists($fullPath)) {

        echo "[ID $id] DATEI FEHLT → Suche...\n";

        $filename = basename($path);
        $found = null;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($archiveBaseDir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $filename) {
                $found = $file->getPathname();
                break;
            }
        }

        if ($found) {

            $newRel = ltrim(str_replace($basePath, '', $found), '/');

            $pdo->prepare("
                UPDATE assets
                SET dokument_pfad = ?, status='ok'
                WHERE id = ?
            ")->execute([$newRel, $id]);

            echo "[ID $id] PFAD KORRIGIERT → $newRel\n";

            continue;

        } else {

            echo "[ID $id] DATEI NICHT GEFUNDEN\n";

            $pdo->prepare("
                UPDATE assets
                SET status = 'error'
                WHERE id = ?
            ")->execute([$id]);

            continue;
        }
    }

    /* =================================================
       SOLL-PFAD
    ================================================= */
    $year = $a["year"] ?: date('Y');

    if (!empty($a["category_name"]) && !empty($a["subcategory_name"])) {

        $cat = dv_sanitize_path_part($a["category_name"]);
        $sub = dv_sanitize_path_part($a["subcategory_name"]);

        $targetDir = $archiveBaseDir . $year . '/' . $cat . '/' . $sub . '/';

    } else {

        $targetDir = $archiveBaseDir . $year . '/Unkategorisiert/';
    }

    /* =================================================
       ORDNER ERSTELLEN
    ================================================= */
    if (!is_dir($targetDir)) {

        if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            echo "[ID $id] FEHLER: Ordner konnte nicht erstellt werden\n";
            continue;
        }
    }

    if (!is_writable($targetDir)) {
        echo "[ID $id] KEINE SCHREIBRECHTE → $targetDir\n";
        continue;
    }

    /* =================================================
       VERSCHIEBEN
    ================================================= */
    $filename   = basename($path);
    $targetPath = dv_unique_target_path($targetDir . $filename);

    if ($fullPath !== $targetPath) {

        if (rename($fullPath, $targetPath)) {

            $newRel = ltrim(str_replace($basePath, '', $targetPath), '/');

            $pdo->prepare("
                UPDATE assets
                SET dokument_pfad = ?, status='ok'
                WHERE id = ?
            ")->execute([$newRel, $id]);

            echo "[ID $id] VERSCHOBEN → $newRel\n";

        } else {

            echo "[ID $id] FEHLER BEIM VERSCHIEBEN\n";
        }
    }
}

echo "=== FERTIG ===\n";