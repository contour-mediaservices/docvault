<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/core/hosting_helper.php";
require_once __DIR__ . "/core/dashboard_helper.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'dashboard';
$moduleTitle  = 'Dashboard';

/* =====================================================
   HELPER
===================================================== */
function safeCount(PDO $pdo, string $sql): int {
    try {
        return (int)$pdo->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function safeFetchAll(PDO $pdo, string $sql): array {
    try {
        $stmt = $pdo->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $e) {
        return [];
    }
}

/* =====================================================
   KPIs
===================================================== */
$assetCount    = safeCount($pdo, "SELECT COUNT(*) FROM assets");
$newAssetCount = getNewAssetsCount();

$projectCount  = safeCount($pdo, "SELECT COUNT(*) FROM projects");
$passwordCount = safeCount($pdo, "SELECT COUNT(*) FROM passwords");

$hostingCount   = safeCount($pdo, "SELECT COUNT(*) FROM hosting_services");
$hostingOverdue = getHostingOverdueCount();
$hostingSoon    = getHostingDueCount();

/* =====================================================
   LETZTE EINTRÄGE (FIXED – KEINE UNSICHEREN SPALTEN)
===================================================== */
$lastAssets = safeFetchAll($pdo, "
    SELECT id, name
    FROM assets
    ORDER BY id DESC
    LIMIT 5
");

$lastProjects = safeFetchAll($pdo, "
    SELECT id, title
    FROM projects
    ORDER BY id DESC
    LIMIT 5
");

$lastPasswords = safeFetchAll($pdo, "
    SELECT id, domain
    FROM passwords
    ORDER BY id DESC
    LIMIT 5
");

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/core/module_layout_start.php";

/* =====================================================
   HEADER (STANDARD)
===================================================== */
$headerConfig = [
    'title' => 'Dashboard'
];

require_once __DIR__ . "/core/header_actions.php";
?>

<div class="row g-4 align-items-stretch">

<!-- Assets -->
<div class="col-md-3">
<a href="/docvault/modules/assets/index.php" class="text-decoration-none text-dark">
<div class="card shadow-sm h-100">
<div class="card-body d-flex flex-column justify-content-between">
<div>
<div class="text-muted">Assets</div>
<div class="fs-3 fw-semibold"><?= $assetCount ?></div>
</div>
<?php if ($newAssetCount > 0): ?>
<div class="mt-2">
<span class="badge bg-warning text-dark">
<?= $newAssetCount ?> neu
</span>
</div>
<?php endif; ?>
</div>
</div>
</a>
</div>

<!-- Projekte -->
<div class="col-md-3">
<a href="/docvault/modules/projects/index.php" class="text-decoration-none text-dark">
<div class="card shadow-sm h-100">
<div class="card-body">
<div class="text-muted">Projekte</div>
<div class="fs-3 fw-semibold"><?= $projectCount ?></div>
</div>
</div>
</a>
</div>

<!-- Passwörter -->
<div class="col-md-3">
<a href="/docvault/modules/passwords/index.php" class="text-decoration-none text-dark">
<div class="card shadow-sm h-100">
<div class="card-body">
<div class="text-muted">Passwörter</div>
<div class="fs-3 fw-semibold"><?= $passwordCount ?></div>
</div>
</div>
</a>
</div>

<!-- Hosting -->
<div class="col-md-3">
<a href="/docvault/modules/hosting/index.php" class="text-decoration-none text-dark">
<div class="card shadow-sm h-100">
<div class="card-body d-flex flex-column justify-content-between">

<div>
<div class="text-muted">Hosting</div>
<div class="fs-3 fw-semibold"><?= $hostingCount ?></div>
</div>

<div class="mt-2 d-flex gap-2 flex-wrap">

<?php if ($hostingOverdue > 0): ?>
<span class="badge bg-danger">
<?= $hostingOverdue ?> überfällig
</span>
<?php endif; ?>

<?php if ($hostingSoon > 0): ?>
<span class="badge bg-warning text-dark">
<?= $hostingSoon ?> bald
</span>
<?php endif; ?>

<?php if ($hostingSoon === 0 && $hostingOverdue === 0): ?>
<span class="badge bg-success">
Alles ok
</span>
<?php endif; ?>

</div>

</div>
</div>
</a>
</div>

</div>

<div class="row g-4 mt-4">

<!-- Assets -->
<div class="col-md-4">
<div class="card shadow-sm h-100">
<div class="card-header bg-white fw-semibold">Neueste Assets</div>
<div class="card-body">
<?php if (empty($lastAssets)): ?>
<div class="text-muted">Keine Einträge</div>
<?php else: ?>
<?php foreach ($lastAssets as $a): ?>
<div class="mb-2">
<a href="/docvault/modules/assets/edit.php?id=<?= (int)$a["id"] ?>" class="text-dark text-decoration-none">
<?= htmlspecialchars((string)$a["name"]) ?>
</a>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>

<!-- Projekte -->
<div class="col-md-4">
<div class="card shadow-sm h-100">
<div class="card-header bg-white fw-semibold">Neueste Projekte</div>
<div class="card-body">
<?php if (empty($lastProjects)): ?>
<div class="text-muted">Keine Einträge</div>
<?php else: ?>
<?php foreach ($lastProjects as $p): ?>
<div class="mb-2">
<a href="/docvault/modules/projects/view.php?id=<?= (int)$p["id"] ?>" class="text-dark text-decoration-none">
<?= htmlspecialchars((string)$p["title"]) ?>
</a>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>

<!-- Passwörter -->
<div class="col-md-4">
<div class="card shadow-sm h-100">
<div class="card-header bg-white fw-semibold">Neueste Passwörter</div>
<div class="card-body">
<?php if (empty($lastPasswords)): ?>
<div class="text-muted">Keine Einträge</div>
<?php else: ?>
<?php foreach ($lastPasswords as $pw): ?>
<div class="mb-2">
<a href="/docvault/modules/passwords/edit.php?id=<?= (int)$pw["id"] ?>" class="text-dark text-decoration-none">
<?= htmlspecialchars((string)$pw["domain"]) ?>
</a>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>

</div>

<?php require_once __DIR__ . "/core/layout_end.php"; ?>