<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../../core/hosting_helper.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'system';
$moduleTitle  = 'System Monitoring';

/* =====================================================
   PFADE
===================================================== */
$basePath       = '/volume1/web/docvault/';
$scanDir        = $basePath . 'scan/';
$processingDir  = $basePath . 'processing/';
$archiveDir     = $basePath . 'archiv/';
$lockFile       = $basePath . 'process.lock';
$repairLock     = $basePath . 'repair.lock';
$logDir         = $basePath . 'logs/';
$logFile        = $logDir . 'scan.log';

$cliScan   = $basePath . 'cli/process_scans.php';
$cliRepair = $basePath . 'cli/repair_assets.php';
$phpBinary = '/usr/local/bin/php84';

/* =====================================================
   ACTIONS
===================================================== */
if (isset($_POST['run_scan']) && !file_exists($lockFile)) {
    shell_exec("$phpBinary $cliScan > /dev/null 2>&1 &");
    header("Location: monitor.php");
    exit;
}

if (isset($_POST['run_repair']) && !file_exists($repairLock)) {
    file_put_contents($repairLock, time());
    shell_exec("$phpBinary $cliRepair > /dev/null 2>&1 &");
    header("Location: monitor.php");
    exit;
}

if (isset($_POST['run_log_cleanup'])) {

    if (file_exists($logFile)) {

        $date = date('Y-m-d');
        $rotated = $logDir . "scan_" . $date . ".log";

        rename($logFile, $rotated);
        touch($logFile);

        @chown($logFile, 'http');
        @chgrp($logFile, 'users');
        @chmod($logFile, 0664);
    }

    foreach (glob($logDir . 'scan_*.log') as $file) {
        if (filemtime($file) < (time() - 10 * 86400)) {
            @unlink($file);
        }
    }

    header("Location: monitor.php");
    exit;
}

/* =====================================================
   HELPER
===================================================== */
function countFiles(string $dir): int {
    if (!is_dir($dir)) return 0;

    $files = array_diff(scandir($dir), ['.', '..', '.DS_Store', 'Thumbs.db']);

    $count = 0;

    foreach ($files as $file) {
        $fullPath = $dir . $file;

        if (
            is_file($fullPath) &&
            !str_starts_with($file, '@') && // Synology @eaDir
            filesize($fullPath) > 0
        ) {
            $count++;
        }
    }

    return $count;
}

function countLogFiles(string $dir): int {
    return count(glob($dir . 'scan_*.log'));
}

function getLastLogRotation(string $dir): string {
    $files = glob($dir . 'scan_*.log');
    if (!$files) return '-';
    usort($files, fn($a,$b) => filemtime($b) <=> filemtime($a));
    return date('d.m.Y H:i', filemtime($files[0]));
}

/* =====================================================
   COUNTS
===================================================== */
$scanCount       = countFiles($scanDir);
$processingCount = countFiles($processingDir);

$assetsCount    = (int)$pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
$passwordsCount = (int)$pdo->query("SELECT COUNT(*) FROM passwords")->fetchColumn();
$projectsCount  = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$hostingCount   = (int)$pdo->query("SELECT COUNT(*) FROM hosting_services")->fetchColumn();

$isScanRunning   = file_exists($lockFile);
$isRepairRunning = file_exists($repairLock);

$logCount = countLogFiles($logDir);
$lastLog  = getLastLogRotation($logDir);

/* =====================================================
   ASSETS
===================================================== */
$newAssets = (int)$pdo->query("SELECT COUNT(*) FROM assets WHERE status='neu'")->fetchColumn();

$brokenAssets = [];
$stmt = $pdo->query("SELECT id, name, dokument_pfad FROM assets ORDER BY id DESC");

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $path = $a["dokument_pfad"] ?? '';
    if ($path !== '') {
        $full = $basePath . $path;
        if (!file_exists($full)) {
            $brokenAssets[] = $a;
        }
    }
}
$brokenCount = count($brokenAssets);

/* =====================================================
   HOSTING
===================================================== */
$hostingDue = function_exists('getHostingOverdueCount')
    ? getHostingOverdueCount()
    : 0;

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

$headerConfig = [
    'title' => 'System Monitoring'
];
require_once __DIR__ . "/../../core/header_actions.php";
?>

<!-- BADGES (statt Hinweise) -->
<div class="card shadow-sm mb-4">
<div class="card-body d-flex gap-3 flex-wrap">

<a href="/docvault/modules/assets/index.php?status=neu" class="badge bg-warning text-decoration-none">
    Neue Assets: <?= $newAssets ?>
</a>

<a href="#errors" class="badge bg-danger text-decoration-none">
    Fehlende Dateien: <?= $brokenCount ?>
</a>

<a href="/docvault/modules/hosting/index.php?filter=due" class="badge bg-danger text-decoration-none">
    Hosting fällig: <?= $hostingDue ?>
</a>

<span class="badge <?= $isScanRunning ? 'bg-warning' : 'bg-success' ?>">
    <?= $isScanRunning ? 'Scan läuft' : 'bereit' ?>
</span>

</div>
</div>

<!-- ZEILE 1 -->
<div class="row g-3">
<div class="col-md-3"><div class="card shadow-sm h-100 text-center"><div class="card-body"><div class="small text-muted">Assets</div><div class="fs-2 fw-bold"><?= $assetsCount ?></div></div></div></div>
<div class="col-md-3"><div class="card shadow-sm h-100 text-center"><div class="card-body"><div class="small text-muted">Projekte</div><div class="fs-2 fw-bold"><?= $projectsCount ?></div></div></div></div>
<div class="col-md-3"><div class="card shadow-sm h-100 text-center"><div class="card-body"><div class="small text-muted">Hosting</div><div class="fs-2 fw-bold"><?= $hostingCount ?></div></div></div></div>
<div class="col-md-3"><div class="card shadow-sm h-100 text-center"><div class="card-body"><div class="small text-muted">Passwörter</div><div class="fs-2 fw-bold"><?= $passwordsCount ?></div></div></div></div>
</div>

<!-- ZEILE 2 -->
<div class="row g-3 mt-1">
<div class="col-md-4"><div class="card shadow-sm h-100 text-center"><div class="card-body"><div class="small text-muted">Scan</div><div class="fs-2 fw-bold"><?= $scanCount ?></div></div></div></div>
<div class="col-md-4"><div class="card shadow-sm h-100 text-center"><div class="card-body"><div class="small text-muted">Processing</div><div class="fs-2 fw-bold"><?= $processingCount ?></div></div></div></div>
<div class="col-md-4"><div class="card shadow-sm h-100 text-center"><div class="card-body"><div class="small text-muted">Logs / Rotation</div><div class="fs-5 fw-bold"><?= $logCount ?> Logs</div><div class="small text-muted"><?= $lastLog ?></div></div></div></div>
</div>

<!-- ZEILE 3 -->
<div class="card shadow-sm mt-4">
<div class="card-body">
<strong>System Status</strong><br>
<span class="text-muted small">
Scan: <?= $isScanRunning ? 'läuft' : 'bereit' ?> |
Repair: <?= $isRepairRunning ? 'läuft' : 'bereit' ?>
</span>
</div>
</div>

<!-- ACTIONS -->
<div class="card shadow-sm mt-4">
<div class="card-body d-flex justify-content-between align-items-center">

<div>
<strong>System Aktionen</strong><br>
<span class="text-muted small">
Scan: <?= $isScanRunning ? 'läuft' : 'bereit' ?> |
Repair: <?= $isRepairRunning ? 'läuft' : 'bereit' ?>
</span>
</div>

<div class="d-flex gap-2">

<form method="post">
<button name="run_scan" class="btn btn-warning">
<i class="bi bi-play"></i> Scan
</button>
</form>

<form method="post">
<button name="run_repair" class="btn btn-outline-warning">
<i class="bi bi-wrench"></i> Auto-Repair
</button>
</form>

<form method="post">
<button name="run_log_cleanup" class="btn btn-outline-secondary">
<i class="bi bi-trash"></i> Logs bereinigen
</button>
</form>

</div>

</div>
</div>

<!-- FEHLERLISTE -->
<div id="errors" class="card shadow-sm mt-4">
<div class="card-body">
<strong>Fehlende Dateien</strong>

<?php if (empty($brokenAssets)): ?>
<div class="text-success mt-2">Keine Fehler vorhanden</div>
<?php else: ?>

<table class="table table-sm table-striped mt-3">
<tr>
<th>ID</th>
<th>Name</th>
<th>Pfad</th>
<th></th>
</tr>

<?php foreach ($brokenAssets as $b): ?>
<tr>
<td><?= (int)$b["id"] ?></td>
<td><?= htmlspecialchars($b["name"]) ?></td>
<td class="text-danger"><?= htmlspecialchars($b["dokument_pfad"]) ?></td>
<td class="text-end">
<a href="/docvault/modules/assets/edit.php?id=<?= (int)$b["id"] ?>" class="btn btn-sm btn-outline-secondary">
<i class="bi bi-pencil"></i>
</a>
</td>
</tr>
<?php endforeach; ?>
</table>

<?php endif; ?>
</div>
</div>

<!-- ZEILE 4: LOG -->
<div class="card shadow-sm mt-4">
<div class="card-body">
<strong>Logs</strong>

<div style="max-height:350px; overflow:auto; font-family:monospace; font-size:12px;" class="mt-2">
<?php
if (file_exists($logFile)) {
    $lines = array_slice(file($logFile), -120);
    foreach ($lines as $line) {
        echo htmlspecialchars($line) . "<br>";
    }
} else {
    echo "Keine Logs vorhanden";
}
?>
</div>

</div>
</div>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>