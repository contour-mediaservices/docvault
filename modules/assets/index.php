<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'assets';

/* =====================================================
   PARAMETER
===================================================== */
$categoryId    = (int)($_GET["category"] ?? 0);
$subcategoryId = (int)($_GET["subcategory"] ?? 0);
$year          = trim($_GET["year"] ?? "");
$status        = $_GET["status"] ?? "";
$tagId         = (int)($_GET["tag"] ?? 0);
$sort          = $_GET["sort"] ?? "year_desc";

/* =====================================================
   FILTER
===================================================== */
$where  = [];
$params = [];

if ($categoryId > 0) {
    $where[] = "a.category_id = :cat";
    $params["cat"] = $categoryId;
}

if ($subcategoryId > 0) {
    $where[] = "a.subcategory_id = :sub";
    $params["sub"] = $subcategoryId;
}

if ($year !== "" && is_numeric($year)) {
    $where[] = "a.year = :year";
    $params["year"] = (int)$year;
}

if ($status === "neu") {
    $where[] = "a.status = 'neu'";
}

if ($status === "error") {
    $where[] = "a.status = 'error'";
}

if ($tagId > 0) {
    $where[] = "ata.tag_id = :tag";
    $params["tag"] = $tagId;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "WHERE 1=1";

/* =====================================================
   SORT
===================================================== */
$orderSql = "a.year DESC";

switch ($sort) {
    case "name_asc":      $orderSql = "a.name ASC"; break;
    case "name_desc":     $orderSql = "a.name DESC"; break;
    case "category_asc":  $orderSql = "c.name ASC"; break;
    case "category_desc": $orderSql = "c.name DESC"; break;
    case "year_asc":      $orderSql = "a.year ASC"; break;
    case "id_desc":       $orderSql = "a.id DESC"; break;
    case "id_asc":        $orderSql = "a.id ASC"; break;
}

/* =====================================================
   DATA
===================================================== */
$stmt = $pdo->prepare("
SELECT 
    a.*,
    c.name AS category_name,
    s.name AS subcategory_name,
    GROUP_CONCAT(t.name SEPARATOR ', ') AS tags
FROM assets a
LEFT JOIN categories c ON c.id = a.category_id
LEFT JOIN subcategories s ON s.id = a.subcategory_id
LEFT JOIN asset_tag_assignments ata ON ata.asset_id = a.id
LEFT JOIN asset_tags t ON t.id = ata.tag_id
$whereSql
GROUP BY a.id
ORDER BY 
CASE 
  WHEN a.status = 'error' THEN 0
  WHEN a.status = 'neu' THEN 1
  ELSE 2
END,
$orderSql,
a.id DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   FILTER DATEN
===================================================== */
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$subcategories = [];
if ($categoryId > 0) {
    $stmt = $pdo->prepare("SELECT id, name FROM subcategories WHERE category_id = ? ORDER BY name");
    $stmt->execute([$categoryId]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$years = $pdo->query("SELECT DISTINCT year FROM assets ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);

$tags = $pdo->query("SELECT id, name FROM asset_tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title'    => 'Assets',
    'show_new' => true,
    'new_url'  => '/docvault/modules/assets/add.php'
];
require_once __DIR__ . "/../../core/header_actions.php";
?>

<!-- FILTERBAR -->
<div class="card shadow-sm mb-3">
<div class="card-body">

<form method="get" class="d-flex flex-wrap align-items-center gap-2">

<a href="?status=neu" class="btn btn-sm <?= $status === 'neu' ? 'btn-warning' : 'btn-outline-secondary' ?>">Neu</a>
<a href="?status=error" class="btn btn-sm <?= $status === 'error' ? 'btn-danger' : 'btn-outline-secondary' ?>">Fehler</a>

<select name="category" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="">Kategorie</option>
<?php foreach ($categories as $c): ?>
<option value="<?= $c["id"] ?>" <?= $categoryId === (int)$c["id"] ? 'selected' : '' ?>>
<?= htmlspecialchars($c["name"]) ?>
</option>
<?php endforeach; ?>
</select>

<select name="subcategory" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="">Unterkategorie</option>
<?php foreach ($subcategories as $s): ?>
<option value="<?= $s["id"] ?>" <?= $subcategoryId === (int)$s["id"] ? 'selected' : '' ?>>
<?= htmlspecialchars($s["name"]) ?>
</option>
<?php endforeach; ?>
</select>

<select name="tag" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="">Alle Tags</option>
<?php foreach ($tags as $t): ?>
<option value="<?= $t["id"] ?>" <?= $tagId === (int)$t["id"] ? 'selected' : '' ?>>
<?= htmlspecialchars($t["name"]) ?>
</option>
<?php endforeach; ?>
</select>

<select name="year" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="">Jahr</option>
<?php foreach ($years as $y): ?>
<option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>>
<?= $y ?>
</option>
<?php endforeach; ?>
</select>

<select name="sort" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="year_desc" <?= $sort === "year_desc" ? 'selected' : '' ?>>Jahr ↓</option>
<option value="year_asc" <?= $sort === "year_asc" ? 'selected' : '' ?>>Jahr ↑</option>
<option value="id_desc" <?= $sort === "id_desc" ? 'selected' : '' ?>>Neueste ↓</option>
<option value="id_asc" <?= $sort === "id_asc" ? 'selected' : '' ?>>Neueste ↑</option>
<option value="name_asc" <?= $sort === "name_asc" ? 'selected' : '' ?>>Name ↑</option>
<option value="name_desc" <?= $sort === "name_desc" ? 'selected' : '' ?>>Name ↓</option>
<option value="category_asc" <?= $sort === "category_asc" ? 'selected' : '' ?>>Kategorie ↑</option>
<option value="category_desc" <?= $sort === "category_desc" ? 'selected' : '' ?>>Kategorie ↓</option>
</select>

<a href="index.php" class="btn btn-sm btn-outline-secondary">Reset</a>

</form>

</div>
</div>

<!-- TABELLE -->
<div class="card shadow-sm">
<div class="card-body p-0">

<div class="table-responsive" style="max-height:70vh; overflow:auto;">
<table class="table table-striped table-hover dv-table mb-0">

<thead>
<tr>
<th>Name</th>
<th>Bezeichnung</th>
<th>Kategorie</th>
<th>Jahr</th>
<th class="text-end">Aktionen</th>
</tr>
</thead>

<tbody>

<?php foreach ($rows as $r): ?>

<tr class="<?=
    ($r["status"] === 'neu') ? 'table-warning' :
    ($r["status"] === 'error' ? 'table-danger' : '')
?>">

<td class="fw-semibold">
<?= htmlspecialchars($r["name"]) ?>
</td>

<td>
<?= htmlspecialchars($r["bezeichnung"] ?? "") ?>
</td>

<td>
<div><?= htmlspecialchars($r["category_name"] ?? "") ?></div>

<?php if (!empty($r["subcategory_name"])): ?>
<div class="text-muted small">
<?= htmlspecialchars($r["subcategory_name"]) ?>
</div>
<?php endif; ?>
</td>

<td>
<?= htmlspecialchars((string)$r["year"]) ?>
</td>

<td class="text-end" style="position:sticky; right:0; background:inherit; white-space:nowrap;">

<a href="view.php?id=<?= $r["id"] ?>" class="btn btn-sm btn-outline-secondary"
data-bs-toggle="tooltip" title="Anzeigen">
<i class="bi bi-eye"></i>
</a>

<a href="edit.php?id=<?= $r["id"] ?>" class="btn btn-sm btn-outline-secondary"
data-bs-toggle="tooltip" title="Bearbeiten">
<i class="bi bi-pencil"></i>
</a>

<form method="post" action="delete.php" style="display:inline;">
<input type="hidden" name="id" value="<?= $r["id"] ?>">
<button class="btn btn-sm btn-outline-danger"
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