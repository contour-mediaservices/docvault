<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../../core/pagination.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'search';
$moduleTitle  = 'Suche';

/* =====================================================
   PARAMETER
===================================================== */
$q = trim($_GET["q"] ?? "");
$page = max(1,(int)($_GET["page"] ?? 1));

$limit = 25;

/* =====================================================
   BOOLEAN SEARCH STRING
===================================================== */
function buildBooleanSearch(string $q): string {

    $terms = preg_split('/\s+/', $q);
    $final = [];

    foreach ($terms as $term) {

        $term = trim($term);

        if ($term !== "") {
            $final[] = '+' . $term . '*';
        }
    }

    return implode(' ',$final);
}

$results = [];
$totalRows = 0;

/* =====================================================
   SEARCH
===================================================== */
if ($q !== "") {

    $search = buildBooleanSearch($q);

    /* COUNT */
    $countSql = "

    SELECT COUNT(*) FROM (

        SELECT a.id
        FROM assets a
        WHERE MATCH(a.name,a.notes)
        AGAINST (:s1 IN BOOLEAN MODE)

        UNION ALL

        SELECT p.id
        FROM projects p
        WHERE MATCH(p.title,p.subtitle,p.short_text,p.long_text)
        AGAINST (:s2 IN BOOLEAN MODE)

        UNION ALL

        SELECT pw.id
        FROM passwords pw
        WHERE MATCH(pw.domain,pw.benutzer,pw.notizen)
        AGAINST (:s3 IN BOOLEAN MODE)

    ) x
    ";

    $stmt = $pdo->prepare($countSql);
    $stmt->execute([
        "s1"=>$search,
        "s2"=>$search,
        "s3"=>$search
    ]);

    $totalRows = (int)$stmt->fetchColumn();

    $pagination = dvPagination($totalRows);

    /* HAUPTSUCHE */
    $sql = "

    SELECT * FROM (

        SELECT
            a.id,
            a.name,
            a.status,
            a.dokument_pfad,
            c.name AS category_name,
            s.name AS subcategory_name,
            'Asset' AS type,
            MATCH(a.name,a.notes)
            AGAINST (:s4 IN BOOLEAN MODE) AS score
        FROM assets a
        LEFT JOIN categories c ON a.category_id=c.id
        LEFT JOIN subcategories s ON a.subcategory_id=s.id
        WHERE MATCH(a.name,a.notes)
        AGAINST (:s5 IN BOOLEAN MODE)

        UNION ALL

        SELECT
            p.id,
            p.title AS name,
            NULL,
            NULL,
            NULL,
            NULL,
            'Projekt',
            MATCH(p.title,p.subtitle,p.short_text,p.long_text)
            AGAINST (:s6 IN BOOLEAN MODE)
        FROM projects p
        WHERE MATCH(p.title,p.subtitle,p.short_text,p.long_text)
        AGAINST (:s7 IN BOOLEAN MODE)

        UNION ALL

        SELECT
            pw.id,
            pw.domain,
            NULL,
            NULL,
            NULL,
            NULL,
            'Passwort',
            MATCH(pw.domain,pw.benutzer,pw.notizen)
            AGAINST (:s8 IN BOOLEAN MODE)
        FROM passwords pw
        WHERE MATCH(pw.domain,pw.benutzer,pw.notizen)
        AGAINST (:s9 IN BOOLEAN MODE)

    ) results

    ORDER BY score DESC

    LIMIT ".$pagination['perPage']." OFFSET ".$pagination['offset']."
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        "s4"=>$search,
        "s5"=>$search,
        "s6"=>$search,
        "s7"=>$search,
        "s8"=>$search,
        "s9"=>$search
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER (STANDARD)
===================================================== */
$headerConfig = [
    'title' => 'Suche'
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<div class="card shadow-sm mb-4">
<div class="card-body">

<form method="get" class="row g-2">

<div class="col-lg-10">

<input class="form-control"
name="q"
value="<?= htmlspecialchars($q) ?>"
placeholder="Suchbegriff eingeben..."
required>

</div>

<div class="col-lg-2">

<button class="btn btn-primary w-100">
<i class="bi bi-search"></i>
Suchen
</button>

</div>

</form>

</div>
</div>


<?php if ($q !== ""): ?>

<h5 class="mb-3"><?= $totalRows ?> Treffer</h5>

<div class="card shadow-sm">
<div class="table-responsive">

<table class="table table-striped table-hover dv-table align-middle mb-0">

<thead class="table-light">
<tr>
<th>Name</th>
<th>Typ</th>
<th>Kategorie</th>
<th>Unterkategorie</th>
<th class="text-end">Aktionen</th>
</tr>
</thead>

<tbody>

<?php if(empty($results)): ?>

<tr>
<td colspan="5" class="text-muted p-4">
Keine Ergebnisse gefunden.
</td>
</tr>

<?php endif; ?>


<?php foreach($results as $r): ?>

<tr class="<?= ($r["status"] ?? "")==="neu" ? 'table-warning':'' ?>">

<td>

<?php if($r["type"]==="Asset"): ?>

<a href="/docvault/modules/assets/edit.php?id=<?= (int)$r["id"] ?>">
<?= htmlspecialchars($r["name"]) ?>
</a>

<?php elseif($r["type"]==="Projekt"): ?>

<a href="/docvault/modules/projects/edit.php?id=<?= (int)$r["id"] ?>">
<?= htmlspecialchars($r["name"]) ?>
</a>

<?php else: ?>

<a href="/docvault/modules/passwords/edit.php?id=<?= (int)$r["id"] ?>">
<?= htmlspecialchars($r["name"]) ?>
</a>

<?php endif; ?>

</td>

<td><?= htmlspecialchars($r["type"]) ?></td>
<td><?= htmlspecialchars($r["category_name"] ?? "") ?></td>
<td><?= htmlspecialchars($r["subcategory_name"] ?? "") ?></td>

<td class="text-end">

<?php if($r["type"]==="Asset"): ?>

<a href="/docvault/modules/assets/view.php?id=<?= (int)$r["id"] ?>"
   class="btn btn-sm btn-outline-secondary">
<i class="bi bi-eye"></i>
</a>

<a href="/docvault/modules/assets/edit.php?id=<?= (int)$r["id"] ?>"
   class="btn btn-sm btn-outline-secondary">
<i class="bi bi-pencil"></i>
</a>

<?php elseif($r["type"]==="Projekt"): ?>

<a href="/docvault/modules/projects/view.php?id=<?= (int)$r["id"] ?>"
   class="btn btn-sm btn-outline-secondary">
<i class="bi bi-eye"></i>
</a>

<a href="/docvault/modules/projects/edit.php?id=<?= (int)$r["id"] ?>"
   class="btn btn-sm btn-outline-secondary">
<i class="bi bi-pencil"></i>
</a>

<?php else: ?>

<a href="/docvault/modules/passwords/edit.php?id=<?= (int)$r["id"] ?>"
   class="btn btn-sm btn-outline-secondary">
<i class="bi bi-pencil"></i>
</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>
</div>

<?php dvPaginationRender($pagination['page'],$pagination['totalPages']); ?>

<?php endif; ?>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>