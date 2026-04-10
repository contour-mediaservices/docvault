<?php
declare(strict_types=1);

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

/* =====================================================
   KATEGORIEN
===================================================== */
$categories = $pdo->query("
    SELECT id, name
    FROM categories
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   TAGS
===================================================== */
$tags = $pdo->query("
    SELECT id, name
    FROM asset_tags
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   BASIS PFADE
===================================================== */
$basePath       = '/volume1/web/docvault/';
$archiveBaseDir = $basePath . 'archiv/';

/* =====================================================
   POST
===================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action        = $_POST["action"] ?? "save";
    $year          = trim((string)($_POST["year"] ?? date('Y')));
    $categoryId    = (int)($_POST["category_id"] ?? 0);
    $subcategoryId = (int)($_POST["subcategory_id"] ?? 0);
    $name          = trim((string)($_POST["name"] ?? ""));
    $bezeichnung   = trim((string)($_POST["bezeichnung"] ?? ""));
    $notes         = (string)($_POST["notes"] ?? "");
    $selectedTags  = $_POST["tags"] ?? [];
    $newTagName    = trim((string)($_POST["new_tag"] ?? ""));

    if ($name !== "") {

        $pdo->beginTransaction();

        try {

            /* ==============================
               DATEI UPLOAD
            ============================== */
            $filePath = '';

            if (!empty($_FILES["file"]["name"]) && $_FILES["file"]["error"] === UPLOAD_ERR_OK) {

                $originalName = basename((string)$_FILES["file"]["name"]);
                $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));

                if ($ext === '') {
                    throw new RuntimeException('Datei hat keine gültige Erweiterung.');
                }

                $safeName = uniqid("doc_", true) . "." . $ext;

                $targetRelativeDir = 'archiv/' . $year . '/Unkategorisiert/';
                $targetDir         = $basePath . $targetRelativeDir;

                if ($categoryId > 0 && $subcategoryId > 0) {
                    $stmtCat = $pdo->prepare("SELECT name FROM categories WHERE id=?");
                    $stmtCat->execute([$categoryId]);
                    $categoryName = (string)($stmtCat->fetchColumn() ?: '');

                    $stmtSub = $pdo->prepare("SELECT name FROM subcategories WHERE id=? AND category_id=?");
                    $stmtSub->execute([$subcategoryId, $categoryId]);
                    $subcategoryName = (string)($stmtSub->fetchColumn() ?: '');

                    if ($categoryName !== '' && $subcategoryName !== '') {
                        $safeCategory    = dv_sanitize_path_part($categoryName);
                        $safeSubcategory = dv_sanitize_path_part($subcategoryName);

                        $targetRelativeDir = 'archiv/' . $year . '/' . $safeCategory . '/' . $safeSubcategory . '/';
                        $targetDir         = $basePath . $targetRelativeDir;
                    }
                }

                if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                    throw new RuntimeException('Upload-Ordner konnte nicht erstellt werden.');
                }

                $targetPath = dv_unique_target_path($targetDir . $safeName);

                if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetPath)) {
                    throw new RuntimeException('Datei konnte nicht hochgeladen werden.');
                }

                $filePath = ltrim(str_replace($basePath, '', $targetPath), '/');
            }

            /* ==============================
               INSERT
            ============================== */
            $pdo->prepare("
                INSERT INTO assets
                (year, category_id, subcategory_id, name, bezeichnung, notes, dokument_pfad, status)
                VALUES (?,?,?,?,?,?,?,'neu')
            ")->execute([
                $year !== '' ? $year : null,
                $categoryId ?: null,
                $subcategoryId ?: null,
                $name,
                $bezeichnung,
                $notes,
                $filePath
            ]);

            $id = (int)$pdo->lastInsertId();

            /* ==============================
               TAG NEU
            ============================== */
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

            /* ==============================
               TAGS ZUORDNEN
            ============================== */
            if (!empty($selectedTags)) {
                $ins = $pdo->prepare("
                    INSERT IGNORE INTO asset_tag_assignments (asset_id, tag_id)
                    VALUES (?, ?)
                ");

                foreach (array_unique(array_map('intval', $selectedTags)) as $tagId) {
                    if ($tagId > 0) {
                        $ins->execute([$id, $tagId]);
                    }
                }
            }

            $pdo->commit();

            /* ==============================
               REDIRECT
            ============================== */
            if ($action === "save_close") {
                header("Location: index.php");
            } elseif ($action === "save_new") {
                header("Location: add.php");
            } else {
                header("Location: edit.php?id=" . $id);
            }
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }
}

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title'     => 'Asset anlegen',
    'show_back' => true,
    'back_url'  => '/docvault/modules/assets/index.php'
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<div class="card shadow-sm">
<div class="card-body">

<form method="post" enctype="multipart/form-data">

<div class="row g-4">

<div class="col-md-3">
<label class="form-label">Jahr</label>
<input type="text"
       name="year"
       class="form-control"
       value="<?= htmlspecialchars((string)($_POST["year"] ?? date('Y'))) ?>">
</div>

<div class="col-md-4">
<label class="form-label">Kategorie</label>
<select name="category_id" class="form-select">
<option value="">-- wählen --</option>
<?php foreach ($categories as $c): ?>
<option value="<?= (int)$c["id"] ?>" <?= ((int)($_POST["category_id"] ?? 0) === (int)$c["id"]) ? 'selected' : '' ?>>
<?= htmlspecialchars((string)$c["name"]) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-5">
<label class="form-label">Unterkategorie</label>
<select name="subcategory_id" class="form-select">
<option value="">-- optional --</option>
</select>
</div>

<div class="col-md-6">
<label class="form-label">Name</label>
<input type="text"
       name="name"
       class="form-control"
       value="<?= htmlspecialchars((string)($_POST["name"] ?? "")) ?>"
       required>
</div>

<div class="col-md-6">
<label class="form-label">Bezeichnung</label>
<input type="text"
       name="bezeichnung"
       class="form-control"
       value="<?= htmlspecialchars((string)($_POST["bezeichnung"] ?? "")) ?>">
</div>

<div class="col-12">
<label class="form-label">Datei (PDF)</label>
<input type="file"
       name="file"
       class="form-control"
       accept="application/pdf">
</div>

<div class="col-12">
<label class="form-label">Notizen</label>
<textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars((string)($_POST["notes"] ?? "")) ?></textarea>
</div>

<div class="col-12">
<label class="form-label">Tags</label>

<div class="d-flex flex-wrap gap-2">
<?php
$currentTagIds = isset($_POST['tags']) && is_array($_POST['tags'])
    ? array_map('intval', $_POST['tags'])
    : [];
?>
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

<input type="text"
       name="new_tag"
       class="form-control mt-2"
       placeholder="Neuer Tag"
       value="<?= htmlspecialchars((string)($_POST["new_tag"] ?? "")) ?>">
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

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>