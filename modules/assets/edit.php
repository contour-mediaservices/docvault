<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'assets';

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
    $dir  = $info['dirname'] ?? '';
    $name = $info['filename'] ?? 'datei';
    $ext  = isset($info['extension']) ? '.' . $info['extension'] : '';

    $i = 1;
    do {
        $candidate = $dir . '/' . $name . '_' . $i . $ext;
        $i++;
    } while (file_exists($candidate));

    return $candidate;
}

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
   ID prüfen
===================================================== */
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

/* =====================================================
   Asset laden
===================================================== */
$stmt = $pdo->prepare("SELECT * FROM assets WHERE id=?");
$stmt->execute([$id]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asset) {
    header("Location: index.php");
    exit;
}

/* =====================================================
   POST
===================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {

    $action        = (string)($_POST["action"] ?? "save");
    $year          = trim((string)($_POST["year"] ?? ""));
    $categoryId    = (int)($_POST["category_id"] ?? 0);
    $subcategoryId = (int)($_POST["subcategory_id"] ?? 0);
    $name          = trim((string)($_POST["name"] ?? ""));
    $bezeichnung   = trim((string)($_POST["bezeichnung"] ?? ""));
    $notes         = (string)($_POST["notes"] ?? "");
    $selectedTags  = $_POST["tags"] ?? [];
    $newTagName    = trim((string)($_POST["new_tag"] ?? ""));

    if ($year !== '' && $categoryId > 0 && $subcategoryId > 0 && $name !== '') {

        try {
            $pdo->beginTransaction();

            $oldPath = trim((string)($asset["dokument_pfad"] ?? ''));

            /* Kategorie prüfen */
            $stmtCat = $pdo->prepare("SELECT name FROM categories WHERE id=?");
            $stmtCat->execute([$categoryId]);
            $newCategoryName = (string)($stmtCat->fetchColumn() ?: '');

            $stmtSub = $pdo->prepare("SELECT name FROM subcategories WHERE id=? AND category_id=?");
            $stmtSub->execute([$subcategoryId, $categoryId]);
            $newSubcategoryName = (string)($stmtSub->fetchColumn() ?: '');

            if ($newCategoryName === '' || $newSubcategoryName === '') {
                throw new RuntimeException('Kategorie oder Unterkategorie ungültig.');
            }

            /* Pfad */
            $basePath        = '/volume1/web/docvault/';
            $archiveBaseDir  = $basePath . 'archiv/';
            $newRelativePath = $oldPath;

            $safeCategory      = dv_sanitize_path_part($newCategoryName);
            $safeSubcategory   = dv_sanitize_path_part($newSubcategoryName);
            $targetRelativeDir = 'archiv/' . $year . '/' . $safeCategory . '/' . $safeSubcategory . '/';
            $targetDir         = $basePath . $targetRelativeDir;

            if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw new RuntimeException('Ordner konnte nicht erstellt werden.');
            }

            /* =========================================
               DATEI UPLOAD / ERSETZEN
            ========================================= */
            if (!empty($_FILES["file"]["name"]) && ($_FILES["file"]["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {

                $originalName = basename((string)$_FILES["file"]["name"]);
                $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));

                if ($ext === '') {
                    throw new RuntimeException('Datei hat keine gültige Erweiterung.');
                }

                $safeName   = uniqid("doc_", true) . "." . $ext;
                $targetPath = dv_unique_target_path($targetDir . $safeName);

                if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetPath)) {
                    throw new RuntimeException('Datei konnte nicht hochgeladen werden.');
                }

                if ($oldPath !== '') {
                    $oldFullPath = $basePath . ltrim($oldPath, '/');

                    if (file_exists($oldFullPath) && is_file($oldFullPath)) {
                        $oldDir = dirname($oldFullPath);
                        @unlink($oldFullPath);
                        dv_cleanup_empty_archive_dirs($oldDir, $archiveBaseDir);
                    }
                }

                $newRelativePath = ltrim(str_replace($basePath, '', $targetPath), '/');

            } elseif ($oldPath !== '') {

                $oldFullPath = $basePath . ltrim($oldPath, '/');

                if (file_exists($oldFullPath) && is_file($oldFullPath)) {

                    $filename         = basename($oldPath);
                    $expectedPath     = $targetDir . $filename;
                    $expectedRelative = ltrim(str_replace($basePath, '', $expectedPath), '/');

                    $normalizedOldRelative = ltrim(str_replace('\\', '/', $oldPath), '/');
                    $normalizedExpected    = ltrim(str_replace('\\', '/', $expectedRelative), '/');

                    if ($normalizedOldRelative !== $normalizedExpected) {

                        $oldDir     = dirname($oldFullPath);
                        $targetPath = $expectedPath;

                        if (file_exists($targetPath) && realpath($targetPath) !== realpath($oldFullPath)) {
                            $targetPath = dv_unique_target_path($targetPath);
                        }

                        if (!rename($oldFullPath, $targetPath)) {
                            throw new RuntimeException('Datei konnte nicht verschoben werden.');
                        }

                        $newRelativePath = ltrim(str_replace($basePath, '', $targetPath), '/');

                        if ($oldDir !== dirname($targetPath)) {
                            dv_cleanup_empty_archive_dirs($oldDir, $archiveBaseDir);
                        }
                    } else {
                        $newRelativePath = $normalizedOldRelative;
                    }
                }
            }

            /* Update */
            $pdo->prepare("
                UPDATE assets
                SET year=?, category_id=?, subcategory_id=?, name=?, bezeichnung=?, notes=?, dokument_pfad=?, status='ok'
                WHERE id=?
            ")->execute([
                $year,
                $categoryId,
                $subcategoryId,
                $name,
                $bezeichnung,



                $notes,
                $newRelativePath,
                $id
            ]);

            /* Tag neu */
            if ($newTagName !== '') {
                $check = $pdo->prepare("SELECT id FROM asset_tags WHERE name=?");
                $check->execute([$newTagName]);
                $existing = $check->fetchColumn();

                if ($existing) {
                    $selectedTags[] = (int)$existing;
                } else {
                    $pdo->prepare("INSERT INTO asset_tags (name) VALUES (?)")
                        ->execute([$newTagName]);
                    $selectedTags[] = (int)$pdo->lastInsertId();
                }
            }

            /* Tags */
            $pdo->prepare("DELETE FROM asset_tag_assignments WHERE asset_id=?")->execute([$id]);

            if (!empty($selectedTags)) {
                $ins = $pdo->prepare("INSERT IGNORE INTO asset_tag_assignments (asset_id, tag_id) VALUES (?,?)");

                foreach (array_unique(array_map('intval', $selectedTags)) as $tagId) {
                    if ($tagId > 0) {
                        $ins->execute([$id, $tagId]);
                    }
                }
            }

            $pdo->commit();

            if ($action === "save_close") {
                header("Location: index.php");
            } else {
                header("Location: edit.php?id=" . $id . "&saved=1");
            }
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            header("Location: edit.php?id=" . $id . "&error=1");
            exit;
        }
    }
}

/* Kategorien */
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$currentCategory = (int)($_POST["category_id"] ?? $asset["category_id"] ?? 0);

$stmt = $pdo->prepare("SELECT id, name FROM subcategories WHERE category_id=? ORDER BY name");
$stmt->execute([$currentCategory]);
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Tags */
if (isset($_POST['tags']) && is_array($_POST['tags'])) {
    $currentTagIds = array_map('intval', $_POST['tags']);
} else {
    $stmt = $pdo->prepare("SELECT tag_id FROM asset_tag_assignments WHERE asset_id=?");
    $stmt->execute([$id]);
    $currentTagIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$tags = $pdo->query("SELECT id, name FROM asset_tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title'     => 'Asset bearbeiten',
    'show_back' => true,
    'back_url'  => '/docvault/modules/assets/index.php',
    'extra'     => [
        [
            'url'   => '/docvault/modules/assets/view.php?id=' . (int)$id,
            'label' => 'Ansicht',
            'icon'  => 'bi-eye',
            'class' => 'btn-warning'
        ]
    ]
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<div class="card shadow-sm">
<div class="card-body">

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success">Asset wurde gespeichert.</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger">Fehler beim Speichern.</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

<div class="row g-4">

<div class="col-md-3">
<label class="form-label">Jahr</label>
<input type="number" name="year" class="form-control"
value="<?= htmlspecialchars((string)($_POST["year"] ?? $asset["year"])) ?>" required>
</div>

<div class="col-md-5">
<label class="form-label">Kategorie</label>
<select name="category_id" class="form-select" onchange="this.form.submit()" required>
<option value="">Bitte wählen</option>
<?php foreach ($categories as $c): ?>
<option value="<?= (int)$c["id"] ?>" <?= $currentCategory === (int)$c["id"] ? 'selected' : '' ?>>
<?= htmlspecialchars($c["name"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-4">
<label class="form-label">Unterkategorie</label>
<select name="subcategory_id" class="form-select" required>
<option value="">Bitte wählen</option>
<?php
$selectedSub = (int)($_POST["subcategory_id"] ?? $asset["subcategory_id"]);
foreach ($subcategories as $s):
?>
<option value="<?= (int)$s["id"] ?>" <?= $selectedSub === (int)$s["id"] ? 'selected' : '' ?>>
<?= htmlspecialchars($s["name"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-6">
<label class="form-label">Name</label>
<input type="text" name="name" class="form-control"
value="<?= htmlspecialchars((string)($_POST["name"] ?? $asset["name"])) ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Bezeichnung</label>
<input type="text" name="bezeichnung" class="form-control"
value="<?= htmlspecialchars((string)($_POST["bezeichnung"] ?? $asset["bezeichnung"] ?? "")) ?>">
</div>

<div class="col-12">
<label class="form-label">Datei hochladen / ersetzen</label>
<input type="file" name="file" class="form-control" accept="application/pdf,image/*">
</div>

<div class="col-12">
<label class="form-label">Notizen</label>
<textarea name="notes" class="form-control" rows="8" style="line-height:1.5;"><?= htmlspecialchars((string)($_POST["notes"] ?? $asset["notes"])) ?></textarea>
</div>

<div class="col-12">
<label class="form-label">Tags</label>

<div class="d-flex flex-wrap gap-2">
<?php foreach ($tags as $t): ?>
<label class="badge bg-light text-dark border">
<input type="checkbox"
       name="tags[]"
       value="<?= (int)$t["id"] ?>"
       <?= in_array((int)$t["id"], $currentTagIds, true) ? 'checked' : '' ?>>
<?= htmlspecialchars($t["name"]) ?>
</label>
<?php endforeach; ?>
</div>

<input type="text" name="new_tag" class="form-control mt-2" placeholder="Neuer Tag">
</div>

<div class="col-12">
<?php
$formConfig = [
    'back_url'  => '/docvault/modules/assets/index.php',
    'important' => true
];
require __DIR__ . "/../../core/form_actions.php";
?>
</div>

</div>

</form>

</div>
</div>

<script src="/docvault/vendor/tinymce/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: 'textarea[name="notes"]',
    license_key: 'gpl',
    height: 400,
    menubar: false,
    branding: false,
    plugins: ['lists', 'link', 'table', 'code'],
    toolbar: 'undo redo | bold italic | bullist numlist | link | table | code',
    content_style: "body { line-height:1.6; font-size:14px; }"
});
</script>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>