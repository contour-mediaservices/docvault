<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'assets';

/* =====================================================
   DATA
===================================================== */
$stmt = $pdo->query("
    SELECT 
        c.*,
        COUNT(s.id) AS sub_count
    FROM categories c
    LEFT JOIN subcategories s ON s.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title' => 'Kategorien',
    'show_new' => true,
    'new_url' => '/docvault/modules/assets/assets_categories/add.php'
];

require_once __DIR__ . "/../../../core/header_actions.php";
?>

<div class="card shadow-sm">
<div class="card-body p-0">

<div class="table-responsive" style="max-height:70vh; overflow:auto;">
<table class="table table-striped table-hover dv-table mb-0">

<thead>
<tr>
<th>Name</th>
<th>Unterkategorien</th>
<th class="text-end">Aktionen</th>
</tr>
</thead>

<tbody>

<?php foreach ($categories as $c): ?>

<tr>

<td class="fw-semibold">
<?= htmlspecialchars($c["name"]) ?>
</td>

<td class="text-muted small">
<?= (int)$c["sub_count"] ?>
</td>

<td class="text-end table-actions">

<a href="edit.php?id=<?= (int)$c["id"] ?>"
   class="action-edit"
   title="Bearbeiten">
<i class="bi bi-pencil"></i>
</a>

<a href="delete.php?id=<?= (int)$c["id"] ?>"
   class="action-delete"
   title="Löschen"
   onclick="return confirm('Kategorie wirklich löschen?')">
<i class="bi bi-trash"></i>
</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>
</div>

</div>
</div>

<?php require_once __DIR__ . "/../../../core/layout_end.php"; ?>