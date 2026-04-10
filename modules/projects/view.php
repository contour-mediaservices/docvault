<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'projects';

/* =====================================================
   ID
===================================================== */
$id = (int)($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id=?");
$stmt->execute([$id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: /docvault/modules/projects/index.php");
    exit;
}

/* =====================================================
   TAGS
===================================================== */
$stmt = $pdo->prepare("
    SELECT t.name
    FROM project_tag_assignments a
    JOIN project_tags t ON t.id = a.tag_id
    WHERE a.project_id=?
    ORDER BY t.name
");
$stmt->execute([$id]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* =====================================================
   FILES
===================================================== */
$stmt = $pdo->prepare("SELECT * FROM project_files WHERE project_id=? ORDER BY id DESC");
$stmt->execute([$id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

$year = date('Y', strtotime($project["created_at"] ?? 'now'));
$webPath = "/docvault/archiv_projekt/$year/$id/";

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title' => $project["title"],
    'show_back' => true,
    'back_url' => '/docvault/modules/projects/index.php',
    'extra' => [
        [
            'url'   => '/docvault/modules/projects/edit.php?id='.$id,
            'label' => 'Bearbeiten',
            'icon'  => 'bi-pencil',
            'class' => 'btn-warning'
        ]
    ]
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<!-- =====================================================
   DATEN
===================================================== -->
<div class="card shadow-sm mb-3">
<div class="card-body">

<div class="row g-4">

<div class="col-md-6">
<label class="form-label">Kunde</label>
<div class="form-control">
<?= htmlspecialchars($project["customer"] ?? "") ?>
</div>
</div>

<div class="col-md-6">
<label class="form-label">Untertitel</label>
<div class="form-control">
<?= htmlspecialchars($project["subtitle"] ?? "") ?>
</div>
</div>

<div class="col-12">
<label class="form-label">Kurzbeschreibung</label>
<div class="form-control" style="min-height: 110px;">
<?= $project["short_text"] ?>
</div>
</div>

<div class="col-12">
<label class="form-label">Beschreibung</label>
<div class="form-control" style="min-height: 180px;">
<?= $project["long_text"] ?>
</div>
</div>

<div class="col-12">
<label class="form-label">Tags</label>

<div class="d-flex flex-wrap gap-2">
<?php if (!empty($tags)): ?>
    <?php foreach ($tags as $t): ?>
        <span class="badge bg-light text-dark border"><?= htmlspecialchars($t) ?></span>
    <?php endforeach; ?>
<?php else: ?>
    <span class="text-muted small">Keine Tags</span>
<?php endif; ?>
</div>
</div>

</div>

</div>
</div>

<!-- =====================================================
   ANHÄNGE
===================================================== -->
<div class="card shadow-sm">
<div class="card-body">

<h6 class="mb-3">Anhänge</h6>

<?php if (empty($files)): ?>

<div class="text-muted small">
Keine Dateien vorhanden
</div>

<?php else: ?>

<div class="table-responsive">
<table class="table table-striped table-hover dv-table mb-0">

<thead>
<tr>
<th>Datei</th>
<th>Typ</th>
<th class="text-end">Aktionen</th>
</tr>
</thead>

<tbody>

<?php foreach ($files as $f):

$ext = strtolower(pathinfo($f["original_name"], PATHINFO_EXTENSION));

?>

<tr>

<td class="fw-semibold">
<?= htmlspecialchars($f["original_name"]) ?>
</td>

<td class="text-muted small">
<?= strtoupper($ext ?: '-') ?>
</td>

<td class="text-end table-actions">

<a href="<?= $webPath.$f["filename"] ?>"
   target="_blank"
   class="action-preview"
   data-bs-toggle="tooltip"
   title="Öffnen">
<i class="bi bi-eye"></i>
</a>

<a href="<?= $webPath.$f["filename"] ?>"
   download
   class="action-edit"
   data-bs-toggle="tooltip"
   title="Download">
<i class="bi bi-download"></i>
</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>
</div>

<?php endif; ?>

</div>
</div>

<script>
document.addEventListener("DOMContentLoaded",function(){
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
new bootstrap.Tooltip(el);
});
});
</script>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>