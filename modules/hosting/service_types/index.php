<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'hosting';
$moduleTitle  = 'Leistungen';

/* =====================================================
   DATEN
===================================================== */
$stmt = $pdo->query("
    SELECT id, name
    FROM hosting_service_types
    ORDER BY name
");

$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title'    => 'Leistungen',
    'show_new' => true,
    'new_url'  => '/docvault/modules/hosting/service_types/add.php'
];

require_once __DIR__ . "/../../../core/header_actions.php";
?>

<div class="card shadow-sm">
<div class="card-body p-0">

<table class="table table-striped table-hover dv-table mb-0">

<thead>
<tr>
<th>Bezeichnung</th>
<th style="width:120px;"></th>
</tr>
</thead>

<tbody>

<?php if (empty($rows)): ?>
<tr>
<td colspan="2" class="text-center text-muted py-4">
Keine Einträge vorhanden
</td>
</tr>
<?php endif; ?>

<?php foreach ($rows as $row): ?>

<tr>

<td>
<a href="/docvault/modules/hosting/service_types/edit.php?id=<?= (int)$row["id"] ?>"
   class="text-decoration-none fw-semibold text-dark">
<?= htmlspecialchars((string)$row["name"]) ?>
</a>
</td>

<td class="text-end">

<a href="/docvault/modules/hosting/service_types/edit.php?id=<?= (int)$row["id"] ?>"
   class="btn btn-sm btn-outline-secondary">
<i class="bi bi-pencil"></i>
</a>

<a href="/docvault/modules/hosting/service_types/delete.php?id=<?= (int)$row["id"] ?>"
   class="btn btn-sm btn-outline-danger"
   onclick="return confirm('Eintrag wirklich löschen?')">
<i class="bi bi-trash"></i>
</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>
</div>

<?php require_once __DIR__ . "/../../../core/layout_end.php"; ?>