<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../../core/pagination.php";
require_once __DIR__ . "/../../core/security_helper.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'passwords';
$moduleTitle  = 'Passwörter';

/* =====================================================
   PARAMETER
===================================================== */
$tagId = (int)($_GET['tag'] ?? 0);

/* =====================================================
   SORT
===================================================== */
$sort = $_GET['sort'] ?? 'domain_asc';

$orderBy = "p.domain ASC";
switch ($sort) {
    case 'domain_desc':   $orderBy = "p.domain DESC"; break;
    case 'benutzer_asc':  $orderBy = "p.benutzer ASC"; break;
    case 'benutzer_desc': $orderBy = "p.benutzer DESC"; break;
    case 'id_desc':       $orderBy = "p.id DESC"; break;
    case 'id_asc':        $orderBy = "p.id ASC"; break;
}

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
   DATA
===================================================== */
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        GROUP_CONCAT(t.name SEPARATOR ', ') AS tags
    FROM passwords p
    LEFT JOIN password_tag_assignments pta ON pta.password_id = p.id
    LEFT JOIN password_tags t ON t.id = pta.tag_id
    $whereSql
    GROUP BY p.id
    ORDER BY $orderBy
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   TAGS
===================================================== */
$tags = $pdo->query("SELECT id, name FROM password_tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title'    => 'Passwörter',
    'show_new' => true,
    'new_url'  => 'add.php'
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<div class="card shadow-sm mb-3">
<div class="card-body">

<form method="get" class="d-flex flex-wrap align-items-center gap-2">

<select name="tag" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="">Alle Tags</option>
<?php foreach ($tags as $t): ?>
<option value="<?= $t["id"] ?>" <?= $tagId === (int)$t["id"] ? 'selected' : '' ?>>
<?= htmlspecialchars($t["name"]) ?>
</option>
<?php endforeach; ?>
</select>

<select name="sort" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="domain_asc" <?= $sort === 'domain_asc' ? 'selected' : '' ?>>Domain ↑</option>
<option value="domain_desc" <?= $sort === 'domain_desc' ? 'selected' : '' ?>>Domain ↓</option>
<option value="benutzer_asc" <?= $sort === 'benutzer_asc' ? 'selected' : '' ?>>Benutzer ↑</option>
<option value="benutzer_desc" <?= $sort === 'benutzer_desc' ? 'selected' : '' ?>>Benutzer ↓</option>
<option value="id_desc" <?= $sort === 'id_desc' ? 'selected' : '' ?>>Neueste ↓</option>
<option value="id_asc" <?= $sort === 'id_asc' ? 'selected' : '' ?>>Neueste ↑</option>
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
<th>Domain</th>
<th>Benutzer</th>
<th>Passwort</th>
<th>PIN</th>
<th style="width:180px;"></th>
</tr>
</thead>

<tbody>

<?php foreach ($rows as $r): ?>
<tr>

<td>
<a href="edit.php?id=<?= (int)$r["id"] ?>"
   data-bs-toggle="tooltip"
   title="Bearbeiten">
<?= htmlspecialchars((string)$r["domain"]) ?>
</a>
</td>

<td><?= htmlspecialchars((string)$r["benutzer"]) ?></td>

<td>
<div class="d-flex align-items-center gap-2">

<input type="password"
class="form-control form-control-sm"
value="<?= htmlspecialchars(dv_decrypt((string)$r["passwort"])) ?>"
readonly
id="pw<?= (int)$r["id"] ?>">

<button class="btn btn-sm btn-outline-secondary"
type="button"
onclick="togglePw(<?= (int)$r['id'] ?>)"
data-bs-toggle="tooltip"
title="Anzeigen">
<i class="bi bi-eye"></i>
</button>

<button class="btn btn-sm btn-outline-secondary"
type="button"
onclick="copyPw(<?= (int)$r['id'] ?>)"
data-bs-toggle="tooltip"
title="Kopieren">
<i class="bi bi-clipboard"></i>
</button>

<button class="btn btn-sm btn-outline-warning"
type="button"
onclick="generateAndCopy()"
data-bs-toggle="tooltip"
title="Neues Passwort generieren">
<i class="bi bi-key"></i>
</button>

</div>
</td>

<td>
<div class="d-flex align-items-center gap-2">

<input type="password"
class="form-control form-control-sm"
value="<?= htmlspecialchars(dv_decrypt((string)$r["pin"])) ?>"
readonly
id="pin<?= (int)$r["id"] ?>">

<button class="btn btn-sm btn-outline-secondary"
type="button"
onclick="togglePin(<?= (int)$r['id'] ?>)"
data-bs-toggle="tooltip"
title="Anzeigen">
<i class="bi bi-eye"></i>
</button>

<button class="btn btn-sm btn-outline-secondary"
type="button"
onclick="copyPin(<?= (int)$r['id'] ?>)"
data-bs-toggle="tooltip"
title="Kopieren">
<i class="bi bi-clipboard"></i>
</button>

</div>
</td>

<td class="text-end">

<a href="edit.php?id=<?= (int)$r["id"] ?>"
class="btn btn-sm btn-outline-secondary"
data-bs-toggle="tooltip"
title="Bearbeiten">
<i class="bi bi-pencil"></i>
</a>

<form method="post"
action="delete.php"
style="display:inline;"
onsubmit="return confirmDelete();">

<input type="hidden" name="id" value="<?= (int)$r["id"] ?>">

<button class="btn btn-sm btn-outline-danger"
type="submit"
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
function togglePw(id){
const f=document.getElementById("pw"+id);
if(!f)return;
f.type=f.type==="password"?"text":"password";
}
function copyPw(id){
const f=document.getElementById("pw"+id);
if(!f)return;
navigator.clipboard.writeText(f.value);
}

function togglePin(id){
const f=document.getElementById("pin"+id);
if(!f)return;
f.type=f.type==="password"?"text":"password";
}
function copyPin(id){
const f=document.getElementById("pin"+id);
if(!f)return;
navigator.clipboard.writeText(f.value);
}

function generateAndCopy(){
const pw=generatePassword();
navigator.clipboard.writeText(pw);
alert("Neues Passwort generiert:\n"+pw);
}
function generatePassword(){
const chars="ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%";
let pw="";
for(let i=0;i<16;i++){
pw+=chars[Math.floor(Math.random()*chars.length)];
}
return pw;
}
function confirmDelete(){
return confirm("Soll dieses Passwort wirklich gelöscht werden?");
}
document.addEventListener("DOMContentLoaded",function(){
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
new bootstrap.Tooltip(el);
});
});
</script>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>