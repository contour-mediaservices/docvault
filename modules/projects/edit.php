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
$tags = $pdo->query("
    SELECT id, name
    FROM project_tags
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT tag_id FROM project_tag_assignments WHERE project_id=?");
$stmt->execute([$id]);
$currentTagIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

/* =====================================================
   POST
===================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "save";

    $title      = trim((string)($_POST["title"] ?? ""));
    $customer   = trim((string)($_POST["customer"] ?? ""));
    $subtitle   = trim((string)($_POST["subtitle"] ?? ""));
    $shortText  = $_POST["short_text"] ?? "";
    $longText   = $_POST["long_text"] ?? "";

    $selectedTags = $_POST["tags"] ?? [];
    $newTagName   = trim((string)($_POST["new_tag"] ?? ""));

    if ($title !== "") {

        try {
            $pdo->beginTransaction();

            $pdo->prepare("
                UPDATE projects
                SET title=?, customer=?, subtitle=?, short_text=?, long_text=?
                WHERE id=?
            ")->execute([
                $title,
                $customer,
                $subtitle,
                $shortText,
                $longText,
                $id
            ]);

            /* FILE UPLOAD */
            if (!empty($_FILES['files']['name'][0])) {

                $year = date('Y', strtotime($project["created_at"] ?? 'now'));
                $baseDir = "/volume1/web/docvault/archiv_projekt/$year/$id/";

                if (!is_dir($baseDir)) {
                    mkdir($baseDir, 0775, true);
                }

                foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {

                    if (($_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $original = basename((string)$_FILES['files']['name'][$i]);
                    $ext = pathinfo($original, PATHINFO_EXTENSION);

                    $filename = uniqid("file_", true) . ($ext !== '' ? "." . $ext : '');
                    $target = $baseDir . $filename;

                    if (move_uploaded_file($tmp, $target)) {
                        $pdo->prepare("
                            INSERT INTO project_files (project_id, filename, original_name)
                            VALUES (?,?,?)
                        ")->execute([$id, $filename, $original]);
                    }
                }
            }

            /* TAG */
            if ($newTagName !== "") {

                $check = $pdo->prepare("SELECT id FROM project_tags WHERE name=?");
                $check->execute([$newTagName]);
                $existing = $check->fetchColumn();

                if ($existing) {
                    $selectedTags[] = (int)$existing;
                } else {
                    $pdo->prepare("INSERT INTO project_tags (name) VALUES (?)")
                        ->execute([$newTagName]);
                    $selectedTags[] = (int)$pdo->lastInsertId();
                }
            }

            /* TAG RESET */
            $pdo->prepare("DELETE FROM project_tag_assignments WHERE project_id=?")->execute([$id]);

            if (!empty($selectedTags)) {
                $ins = $pdo->prepare("
                    INSERT IGNORE INTO project_tag_assignments
                    (project_id, tag_id)
                    VALUES (?,?)
                ");
                foreach (array_unique(array_map('intval', $selectedTags)) as $tagId) {
                    if ($tagId > 0) {
                        $ins->execute([$id, $tagId]);
                    }
                }
            }

            $pdo->commit();

            if ($action === "save_close") {
                header("Location: /docvault/modules/projects/index.php");
            } else {
                header("Location: edit.php?id=".$id."&saved=1");
            }
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            header("Location: edit.php?id=".$id."&error=1");
            exit;
        }
    }
}

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

$headerConfig = [
    'title' => 'Projekt bearbeiten',
    'show_back' => true,
    'back_url' => '/docvault/modules/projects/index.php'
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success">Gespeichert</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger">Fehler beim Speichern</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card shadow-sm">
<div class="card-body">

<div class="row g-4">

<div class="col-md-6">
<label class="form-label">Titel</label>
<input type="text" name="title" class="form-control"
value="<?= htmlspecialchars((string)$project["title"]) ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Kunde</label>
<input type="text" name="customer" class="form-control"
value="<?= htmlspecialchars((string)($project["customer"] ?? "")) ?>">
</div>

<div class="col-12">
<label class="form-label">Untertitel</label>
<input type="text" name="subtitle" class="form-control"
value="<?= htmlspecialchars((string)($project["subtitle"] ?? "")) ?>">
</div>

<div class="col-12">
<label class="form-label">Kurzbeschreibung</label>
<textarea name="short_text" class="form-control"><?= htmlspecialchars((string)($project["short_text"] ?? "")) ?></textarea>
</div>

<div class="col-12">
<label class="form-label">Beschreibung</label>
<textarea name="long_text" class="form-control"><?= htmlspecialchars((string)($project["long_text"] ?? "")) ?></textarea>
</div>

<div class="col-12">
<label class="form-label">Dateien hinzufügen</label>
<input type="file" name="files[]" multiple class="form-control">
</div>

<!-- TAGS -->
<div class="col-12">
<label class="form-label">Tags</label>

<div class="d-flex flex-wrap gap-2">
<?php foreach ($tags as $t): ?>
<label class="badge bg-light text-dark border">
<input type="checkbox"
       name="tags[]"
       value="<?= (int)$t["id"] ?>"
       <?= in_array((int)$t["id"], $currentTagIds, true) ? 'checked' : '' ?>>
<?= htmlspecialchars((string)$t["name"]) ?>
</label>
<?php endforeach; ?>
</div>

<input type="text" name="new_tag" class="form-control mt-2" placeholder="Neuer Tag">
</div>

<div class="col-12">
<?php
$formConfig = [
    'back_url'  => '/docvault/modules/projects/index.php',
    'important' => true
];
require __DIR__ . "/../../core/form_actions.php";
?>
</div>

</div>


</div>
</form>

<!-- ANHÄNGE -->
<div class="card shadow-sm mt-3">
<div class="card-body">

<div class="d-flex justify-content-between align-items-center mb-2">
<h6 class="mb-0">Anhänge</h6>
<small class="text-muted"><?= count($files) ?> Datei(en)</small>
</div>

<?php if (empty($files)): ?>
<div class="text-muted small">Keine Anhänge vorhanden</div>
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

<a href="<?= $webPath.$f["filename"] ?>" target="_blank" class="action-preview"
data-bs-toggle="tooltip" title="Öffnen">
<i class="bi bi-eye"></i>
</a>

<a href="<?= $webPath.$f["filename"] ?>" download class="action-edit"
data-bs-toggle="tooltip" title="Download">
<i class="bi bi-download"></i>
</a>

<form method="post" action="delete_file.php" style="display:inline;">
<input type="hidden" name="id" value="<?= $f["id"] ?>">
<input type="hidden" name="project_id" value="<?= $id ?>">
<button class="action-delete"
data-bs-toggle="tooltip" title="Löschen">
<i class="bi bi-trash"></i>
</button>
</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>
</div>

<?php endif; ?>

</div>
</div>

<script src="/docvault/vendor/tinymce/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: 'textarea[name="short_text"], textarea[name="long_text"]',
    license_key: 'gpl',
    height: 300,
    menubar: false,
    branding: false,
    plugins: ['lists', 'link', 'table', 'code'],
    toolbar: 'undo redo | bold italic | bullist numlist | link | table | code'
});

document.addEventListener("DOMContentLoaded",function(){
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
new bootstrap.Tooltip(el);
});
});
</script>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>