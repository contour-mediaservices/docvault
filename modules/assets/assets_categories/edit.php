<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../config.php";

$activeModule = 'assets';

/* ID */
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

/* Kategorie */
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    header("Location: index.php");
    exit;
}

/* =========================
   POST SUB (WICHTIG: zuerst!)
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["type"] ?? '') === 'sub_add') {

    $name = trim($_POST["sub_name"] ?? "");
    $categoryId = (int)($_POST["category_id"] ?? 0);

    if ($name !== "" && $categoryId > 0) {

        $pdo->prepare("
            INSERT INTO subcategories (category_id, name)
            VALUES (?, ?)
        ")->execute([$categoryId, $name]);
    }

    header("Location: edit.php?id=".$id);
    exit;
}

/* =========================
   POST KATEGORIE
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["type"] ?? '') === 'category') {

    $name = trim($_POST["name"] ?? "");

    if ($name !== "") {
        $pdo->prepare("UPDATE categories SET name=? WHERE id=?")
            ->execute([$name, $id]);
    }

    $action = $_POST['action'] ?? 'save';

    if ($action === 'save_close') {
        header("Location: index.php");
        exit;
    }

    header("Location: edit.php?id=".$id);
    exit;
}

/* Subs */
$subs = $pdo->prepare("
    SELECT * FROM subcategories
    WHERE category_id=?
    ORDER BY name
");
$subs->execute([$id]);
$subcategories = $subs->fetchAll();

/* LAYOUT */
require_once __DIR__ . "/../../../core/module_layout_start.php";

/* HEADER */
$headerConfig = [
    'title' => 'Kategorie bearbeiten',
    'show_back' => true,
    'back_url' => 'index.php'
];

require_once __DIR__ . "/../../../core/header_actions.php";
?>

<div class="card shadow-sm mb-3">
<div class="card-body">

<form method="post">
<input type="hidden" name="type" value="category">

<div class="row g-4">

<div class="col-12">
<label class="form-label">Name</label>
<input type="text" name="name"
value="<?= htmlspecialchars($category['name']) ?>"
class="form-control">
</div>

<div class="col-12">
<?php
$formConfig = [
    'back_url' => 'index.php',
    'important' => true,
    'show_save_new' => false
];
require __DIR__ . "/../../../core/form_actions.php";
?>
</div>

</div>
</form>

</div>
</div>

<div class="card shadow-sm">
<div class="card-body">

<label class="form-label">Unterkategorien</label>

<form method="post" class="d-flex gap-2 mb-1">
<input type="hidden" name="type" value="sub_add">
<input type="hidden" name="category_id" value="<?= $id ?>">

<input type="text" name="sub_name"
       class="form-control"
       placeholder="Neue Unterkategorie">

<button type="submit"
        class="btn btn-warning"
        data-bs-toggle="tooltip"
        title="Unterkategorie hinzufügen">
    <i class="bi bi-plus"></i>
</button>
</form>

<small class="text-muted d-block mb-3">
Unterkategorie mit + hinzufügen
</small>

<table class="table table-striped table-hover dv-table">
<tbody>

<?php foreach ($subcategories as $s): ?>
<tr>

<td><?= htmlspecialchars($s['name']) ?></td>

<td class="text-end">
<a href="delete.php?sub=<?= (int)$s['id'] ?>&cat=<?= $id ?>"
   class="btn btn-sm btn-outline-danger"
   data-bs-toggle="tooltip"
   title="Unterkategorie löschen"
   onclick="return confirm('Löschen?')">
<i class="bi bi-trash"></i>
</a>
</td>

</tr>
<?php endforeach; ?>

</tbody>
</table>

</div>
</div>

<script>
document.addEventListener("DOMContentLoaded",function(){
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
new bootstrap.Tooltip(el);
});
});
</script>

<?php require_once __DIR__ . "/../../../core/layout_end.php"; ?>