<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'hosting';

/* =====================================================
   PARAMETER
===================================================== */
$customer = trim($_GET["customer"] ?? "");
$service  = (int)($_GET["service"] ?? 0);
$cycle    = $_GET["cycle"] ?? "";
$status   = $_GET["status"] ?? "";
$sort     = $_GET["sort"] ?? "priority";

/* =====================================================
   FILTER
===================================================== */
$where = [];
$params = [];

if ($customer !== "") {
    $where[] = "hs.customer LIKE :customer";
    $params["customer"] = "%$customer%";
}

if ($service > 0) {
    $where[] = "hsa.service_type_id = :service";
    $params["service"] = $service;
}

if ($cycle !== "") {
    $where[] = "hs.billing_cycle = :cycle";
    $params["cycle"] = $cycle;
}

if ($status === "overdue") {
    $where[] = "hs.billing_date <= CURDATE()";
}

if ($status === "soon") {
    $where[] = "hs.billing_date > CURDATE() 
                AND hs.billing_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* =====================================================
   SORT
===================================================== */
switch ($sort) {

    case "date_asc":  $orderSql = "hs.billing_date ASC"; break;
    case "date_desc": $orderSql = "hs.billing_date DESC"; break;
    case "domain_asc":  $orderSql = "hs.domain ASC"; break;
    case "domain_desc": $orderSql = "hs.domain DESC"; break;

    default:
        $orderSql = "
        CASE
            WHEN hs.billing_date <= CURDATE() THEN 0
            WHEN hs.billing_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1
            ELSE 2
        END,
        hs.billing_date ASC
        ";
}

/* =====================================================
   DATA
===================================================== */
$stmt = $pdo->prepare("
SELECT 
    hs.*,
    GROUP_CONCAT(st.name SEPARATOR '||') AS services
FROM hosting_services hs
LEFT JOIN hosting_service_assignments hsa ON hsa.hosting_id = hs.id
LEFT JOIN hosting_service_types st ON st.id = hsa.service_type_id
$whereSql
GROUP BY hs.id
ORDER BY $orderSql, hs.id DESC
");

$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* FILTER DATEN */
$services = $pdo->query("
    SELECT id, name
    FROM hosting_service_types
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

/* STATUS HELPER */
function getHostingStatus(array $r): string {
    $date = $r["billing_date"] ?? null;
    if (!$date) return 'ok';

    if ($date <= date('Y-m-d')) return 'overdue';
    if ($date <= date('Y-m-d', strtotime('+30 days'))) return 'soon';

    return 'ok';
}

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title' => 'Hosting',
    'show_new' => true,
    'new_url' => 'add.php'
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<!-- FILTER -->
<div class="card shadow-sm mb-3">
<div class="card-body">

<form method="get" class="d-flex flex-wrap align-items-center gap-2">

<a href="?status=overdue"
   class="btn btn-sm <?= $status === 'overdue' ? 'btn-danger' : 'btn-outline-secondary' ?>">
Überfällig
</a>

<a href="?status=soon"
   class="btn btn-sm <?= $status === 'soon' ? 'btn-warning' : 'btn-outline-secondary' ?>">
Bald
</a>

<input type="text" name="customer"
value="<?= htmlspecialchars($customer) ?>"
placeholder="Kunde"
class="form-control form-control-sm w-auto"
onchange="this.form.submit()">

<select name="service" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="">Leistung</option>
<?php foreach ($services as $s): ?>
<option value="<?= $s["id"] ?>" <?= $service==$s["id"]?'selected':'' ?>>
<?= htmlspecialchars($s["name"]) ?>
</option>
<?php endforeach; ?>
</select>

<select name="cycle" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="">Zyklus</option>
<option value="monthly" <?= $cycle==='monthly'?'selected':'' ?>>monatlich</option>
<option value="yearly" <?= $cycle==='yearly'?'selected':'' ?>>jährlich</option>
</select>

<select name="sort" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
<option value="priority">Priorität</option>
<option value="date_asc">Datum ↑</option>
<option value="date_desc">Datum ↓</option>
<option value="domain_asc">Domain ↑</option>
<option value="domain_desc">Domain ↓</option>
</select>

<a href="index.php" class="btn btn-sm btn-outline-secondary">Reset</a>

</form>

</div>
</div>

<!-- TABLE -->
<div class="card shadow-sm">
<div class="card-body p-0">

<table class="table table-striped table-hover dv-table mb-0">

<thead>
<tr>
<th>Domain</th>
<th>Kunde</th>
<th>Leistungen</th>
<th>Datum</th>
<th></th>
</tr>
</thead>

<tbody>

<?php foreach ($rows as $r): 
$statusRow = getHostingStatus($r);
$rowClass = $statusRow === 'overdue' ? 'table-danger' : ($statusRow === 'soon' ? 'table-warning' : '');
?>

<tr class="<?= $rowClass ?>">

<td>
<a href="edit.php?id=<?= $r["id"] ?>" class="fw-semibold text-dark text-decoration-none">
<?= htmlspecialchars($r["domain"]) ?>
</a>
</td>

<td><?= htmlspecialchars($r["customer"]) ?></td>

<td>
<?php foreach (explode('||',$r["services"] ?? '') as $s):
if(!$s) continue; ?>
<span class="badge bg-light text-dark border"><?= htmlspecialchars($s) ?></span>
<?php endforeach; ?>
</td>

<td><?= htmlspecialchars($r["billing_date"]) ?></td>

<td class="text-end d-flex justify-content-end gap-1">

<a href="mark_paid.php?id=<?= $r['id'] ?>"
class="btn btn-sm <?= $statusRow==='overdue'?'btn-danger':($statusRow==='soon'?'btn-warning':'btn-outline-secondary') ?>"
onclick="return confirm('Als erledigt markieren?');">
<i class="bi bi-check2-circle"></i>
</a>

<a href="edit.php?id=<?= $r["id"] ?>" class="btn btn-sm btn-outline-secondary">
<i class="bi bi-pencil"></i>
</a>

<form method="post" action="delete.php">
<input type="hidden" name="id" value="<?= $r["id"] ?>">
<button class="btn btn-sm btn-outline-danger">
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

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>