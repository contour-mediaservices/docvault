<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../../core/pagination.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'passwords';
$moduleTitle  = 'Passwort-Tags';

$action = $_GET['action'] ?? null;

/* =====================================================
   CREATE
===================================================== */
if ($action === "create" && !empty($_POST['name'])) {
    $pdo->prepare("INSERT IGNORE INTO password_tags (name) VALUES (?)")
        ->execute([trim($_POST['name'])]);

    header("Location: index.php");
    exit;
}

/* =====================================================
   UPDATE
===================================================== */
if ($action === "update" && !empty($_POST['id']) && isset($_POST['name'])) {
    $pdo->prepare("UPDATE password_tags SET name=? WHERE id=?")
        ->execute([trim($_POST['name']), (int)$_POST['id']]);

    header("Location: index.php");
    exit;
}

/* =====================================================
   DELETE
===================================================== */
if ($action === "delete" && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];

    $check = $pdo->prepare("SELECT COUNT(*) FROM password_tag_assignments WHERE tag_id=?");
    $check->execute([$id]);

    if ($check->fetchColumn() == 0) {
        $pdo->prepare("DELETE FROM password_tags WHERE id=?")->execute([$id]);
    }

    header("Location: index.php");
    exit;
}

/* =====================================================
   DATA
===================================================== */
$totalRows = (int)$pdo->query("SELECT COUNT(*) FROM password_tags")->fetchColumn();
$pagination = dvPagination($totalRows);

$tags = $pdo->query("
SELECT t.*, COUNT(m.password_id) AS usage_count
FROM password_tags t
LEFT JOIN password_tag_assignments m ON t.id = m.tag_id
GROUP BY t.id
ORDER BY t.name
LIMIT ".$pagination['perPage']." OFFSET ".$pagination['offset']."
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER (STANDARD)
===================================================== */
$headerConfig = [
    'title' => 'Passwort-Tags',
    'show_back' => true,
    'back_url' => '/docvault/modules/passwords/index.php'
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<!-- TAG ANLEGEN -->
<div class="card shadow-sm mb-3">
<div class="card-body">

<form method="post" action="?action=create" class="d-flex gap-2">

<input type="text"
       name="name"
       class="form-control form-control-sm"
       placeholder="Neuer Tag"
       required>

<button class="btn btn-warning btn-sm">
<i class="bi bi-plus-circle"></i>
</button>

</form>

</div>
</div>


<!-- TAG LISTE -->
<div class="card shadow-sm">
<div class="table-responsive">

<table class="table table-striped table-hover dv-table align-middle mb-0">

<thead>
<tr>
<th>Tag</th>
<th>Verwendet</th>
<th class="text-end"></th>
</tr>
</thead>

<tbody>

<?php if (empty($tags)): ?>

<tr>
<td colspan="3" class="text-muted p-4">
Noch keine Tags vorhanden.
</td>
</tr>

<?php else: ?>

<?php foreach ($tags as $tag): ?>

<tr>

<td>

<span id="label-<?= $tag['id'] ?>"
      class="fw-semibold"
      style="cursor:pointer"
      onclick="editTag(<?= $tag['id'] ?>)">

<?= htmlspecialchars($tag['name']) ?>

</span>

<form method="post"
      action="?action=update"
      id="form-<?= $tag['id'] ?>"
      class="d-none">

<input type="hidden" name="id" value="<?= $tag['id'] ?>">

<input type="text"
       name="name"
       value="<?= htmlspecialchars($tag['name']) ?>"
       class="form-control form-control-sm">

</form>

</td>

<td><?= $tag['usage_count'] ?></td>

<td class="text-end">

<button type="button"
        class="btn btn-sm btn-outline-secondary"
        onclick="editTag(<?= $tag['id'] ?>)">
<i class="bi bi-pencil"></i>
</button>

<?php if ($tag['usage_count'] == 0): ?>

<a href="?action=delete&id=<?= $tag['id'] ?>"
   class="btn btn-sm btn-outline-danger"
   onclick="return confirm('Tag wirklich löschen?');">
<i class="bi bi-trash"></i>
</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>
</div>
</div>

<?php dvPaginationRender($pagination['page'],$pagination['totalPages']); ?>

<script>
function editTag(id){
    document.getElementById('label-'+id).classList.add('d-none');
    document.getElementById('form-'+id).classList.remove('d-none');
}
</script>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>