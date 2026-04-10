<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'projects';

/* =====================================================
   TAGS LADEN
===================================================== */
$tags = $pdo->query("
    SELECT id, name
    FROM project_tags
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

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
                INSERT INTO projects
                (title, customer, subtitle, short_text, long_text, created_at)
                VALUES (?,?,?,?,?, NOW())
            ")->execute([
                $title,
                $customer,
                $subtitle,
                $shortText,
                $longText
            ]);

            $projectId = (int)$pdo->lastInsertId();

            if (!empty($_FILES['files']['name'][0])) {

                $year = date('Y');
                $baseDir = "/volume1/web/docvault/archiv_projekt/$year/$projectId/";

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
                        ")->execute([$projectId, $filename, $original]);
                    }
                }
            }

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

            if (!empty($selectedTags)) {

                $ins = $pdo->prepare("
                    INSERT IGNORE INTO project_tag_assignments
                    (project_id, tag_id)
                    VALUES (?,?)
                ");

                foreach (array_unique(array_map('intval', $selectedTags)) as $tagId) {
                    if ($tagId > 0) {
                        $ins->execute([$projectId, $tagId]);
                    }
                }
            }

            $pdo->commit();

            if ($action === "save_close") {
                header("Location: /docvault/modules/projects/index.php");
            } elseif ($action === "save_new") {
                header("Location: /docvault/modules/projects/add.php");
            } else {
                header("Location: /docvault/modules/projects/edit.php?id=" . $projectId);
            }
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            header("Location: /docvault/modules/projects/add.php?error=1");
            exit;
        }
    } else {
        header("Location: /docvault/modules/projects/add.php?error=1");
        exit;
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
    'title'     => 'Projekt anlegen',
    'show_back' => true,
    'back_url'  => '/docvault/modules/projects/index.php'
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger">Bitte Titel eingeben.</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card shadow-sm">
<div class="card-body">

<div class="row g-4">

<div class="col-md-6">
<label class="form-label">Titel</label>
<input type="text" name="title" class="form-control"
value="<?= htmlspecialchars((string)($_POST["title"] ?? "")) ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Kunde</label>
<input type="text" name="customer" class="form-control"
value="<?= htmlspecialchars((string)($_POST["customer"] ?? "")) ?>">
</div>

<div class="col-12">
<label class="form-label">Untertitel</label>
<input type="text" name="subtitle" class="form-control"
value="<?= htmlspecialchars((string)($_POST["subtitle"] ?? "")) ?>">
</div>

<div class="col-12">
<label class="form-label">Kurzbeschreibung</label>
<textarea name="short_text" class="form-control"><?= htmlspecialchars((string)($_POST["short_text"] ?? "")) ?></textarea>
</div>

<div class="col-12">
<label class="form-label">Beschreibung</label>
<textarea name="long_text" class="form-control"><?= htmlspecialchars((string)($_POST["long_text"] ?? "")) ?></textarea>
</div>

<div class="col-12">
<label class="form-label">Dateien hochladen</label>
<input type="file" name="files[]" class="form-control" multiple>
</div>

<!-- TAGS -->
<div class="col-12">
<label class="form-label">Tags</label>

<?php
$currentTagIds = isset($_POST['tags']) && is_array($_POST['tags'])
    ? array_map('intval', $_POST['tags'])
    : [];
?>

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

<input type="text" name="new_tag" class="form-control mt-2"
placeholder="Neuer Tag"
value="<?= htmlspecialchars((string)($_POST["new_tag"] ?? "")) ?>">
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
</script>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>