<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'projects';
$moduleTitle  = 'Projekte';

/* =====================================================
   PARAMETER
===================================================== */
$tagId = (int)($_GET["tag"] ?? 0);
$sort  = $_GET["sort"] ?? "id_desc";

/* =====================================================
   FILTER
===================================================== */
$where  = [];
$params = [];

if ($tagId > 0) {
    $where[] = "pta.tag_id = :tag";
    $params["tag"] = $tagId;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* =====================================================
   SORT
===================================================== */
$orderSql = "p.id DESC";

switch ($sort) {
    case "title_asc":      $orderSql = "p.title ASC"; break;
    case "title_desc":     $orderSql = "p.title DESC"; break;
    case "customer_asc":   $orderSql = "p.customer ASC"; break;
    case "customer_desc":  $orderSql = "p.customer DESC"; break;
    case "tag_asc":        $orderSql = "tags ASC"; break;
    case "tag_desc":       $orderSql = "tags DESC"; break;
    case "id_asc":         $orderSql = "p.id ASC"; break;
    case "id_desc":        $orderSql = "p.id DESC"; break;
}

/* =====================================================
   DATA
===================================================== */
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        COUNT(DISTINCT f.id) AS file_count,
        GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS tags
    FROM projects p
    LEFT JOIN project_files f ON f.project_id = p.id
    LEFT JOIN project_tag_assignments pta ON pta.project_id = p.id
    LEFT JOIN project_tags t ON t.id = pta.tag_id
    $whereSql
    GROUP BY p.id
    ORDER BY $orderSql, p.id DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   FILTER DATEN
===================================================== */
$tags = $pdo->query("
    SELECT id, name
    FROM project_tags
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . "/../../core/module_layout_start.php";

$headerConfig = [
    'title'    => 'Projekte',
    'show_new' => true,
    'new_url'  => '/docvault/modules/projects/add.php'
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<div class="card shadow-sm mb-3">
<div class="card-body">

<form method="get" class="d-flex flex-wrap align-items-center gap-2">

<select name="tag" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="">Alle Tags</option>
<?php foreach ($tags as $t): ?>
<option value="<?= (int)$t["id"] ?>" <?= $tagId === (int)$t["id"] ? 'selected' : '' ?>>
<?= htmlspecialchars((string)$t["name"]) ?>
</option>
<?php endforeach; ?>
</select>

<select name="sort" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="id_desc" <?= $sort === "id_desc" ? 'selected' : '' ?>>Neueste ↓</option>
<option value="id_asc" <?= $sort === "id_asc" ? 'selected' : '' ?>>Neueste ↑</option>
<option value="title_asc" <?= $sort === "title_asc" ? 'selected' : '' ?>>Titel ↑</option>
<option value="title_desc" <?= $sort === "title_desc" ? 'selected' : '' ?>>Titel ↓</option>
<option value="customer_asc" <?= $sort === "customer_asc" ? 'selected' : '' ?>>Kunde ↑</option>
<option value="customer_desc" <?= $sort === "customer_desc" ? 'selected' : '' ?>>Kunde ↓</option>
<option value="tag_asc" <?= $sort === "tag_asc" ? 'selected' : '' ?>>Tags ↑</option>
<option value="tag_desc" <?= $sort === "tag_desc" ? 'selected' : '' ?>>Tags ↓</option>
</select>

<a href="index.php" class="btn btn-sm btn-outline-secondary">Reset</a>

</form>

</div>
</div>

<div class="card shadow-sm">
<div class="card-body p-0">

<table class="table table-striped table-hover dv-table mb-0">

<thead>
<tr>
<th>Projekt</th>
<th>Kunde</th>
<th>Dateien</th>
<th style="width:140px;"></th>
</tr>
</thead>

<tbody>

<?php if (empty($rows)): ?>
<tr>
<td colspan="4" class="text-center text-muted py-4">
Keine Projekte vorhanden
</td>
</tr>
<?php endif; ?>

<?php foreach ($rows as $row): ?>
<tr>

<td>
<a href="/docvault/modules/projects/edit.php?id=<?= (int)$row["id"] ?>"
class="fw-semibold text-dark text-decoration-none">
<?= htmlspecialchars((string)$row["title"]) ?>
</a>
</td>

<td><?= htmlspecialchars((string)($row["customer"] ?? "")) ?></td>

<td><?= (int)$row["file_count"] ?></td>

<td class="text-end">

<a href="/docvault/modules/projects/view.php?id=<?= (int)$row["id"] ?>"
class="btn btn-sm btn-outline-secondary"
data-bs-toggle="tooltip"
title="Anzeigen">
<i class="bi bi-eye"></i>
</a>

<a href="/docvault/modules/projects/edit.php?id=<?= (int)$row["id"] ?>"
class="btn btn-sm btn-outline-secondary"
data-bs-toggle="tooltip"
title="Bearbeiten">
<i class="bi bi-pencil"></i>
</a>

<form method="post"
action="/docvault/modules/projects/delete.php"
style="display:inline;"
onsubmit="return confirm('Projekt löschen?');">

<input type="hidden" name="id" value="<?= (int)$row["id"] ?>">

<button class="btn btn-sm btn-outline-danger"
data-bs-toggle="tooltip"
title="Löschen">
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

<script>
document.addEventListener("DOMContentLoaded",function(){
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
new bootstrap.Tooltip(el);
});
});
</script>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>