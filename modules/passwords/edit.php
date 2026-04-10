<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../../core/security_helper.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'passwords';
$moduleTitle  = 'Passwort bearbeiten';

/* =====================================================
   ID
===================================================== */
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

/* =====================================================
   DATEN LADEN
===================================================== */
$stmt = $pdo->prepare("SELECT * FROM passwords WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: index.php");
    exit;
}

/* =====================================================
   TAGS
===================================================== */
$tags = $pdo->query("
    SELECT id, name
    FROM password_tags
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$selected = $pdo->prepare("
    SELECT tag_id
    FROM password_tag_assignments
    WHERE password_id = ?
");
$selected->execute([$id]);
$currentTagIds = array_map(
    'intval',
    array_column($selected->fetchAll(PDO::FETCH_ASSOC), 'tag_id')
);

/* =====================================================
   POST
===================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action    = $_POST["action"] ?? "save";
    $domain    = trim((string)($_POST["domain"] ?? ""));
    $benutzer  = trim((string)($_POST["benutzer"] ?? ""));
    $passwort  = trim((string)($_POST["passwort"] ?? ""));
    $pin       = trim((string)($_POST["pin"] ?? ""));
    $datum     = trim((string)($_POST["datum"] ?? ""));
    $notizen   = trim((string)($_POST["notizen"] ?? ""));
    $tagsNew   = $_POST["tags"] ?? [];
    $newTag    = trim((string)($_POST["new_tag"] ?? ""));

    try {
        $pdo->beginTransaction();

        $pdo->prepare("
            UPDATE passwords
            SET domain = ?, benutzer = ?, passwort = ?, pin = ?, datum = ?, notizen = ?
            WHERE id = ?
        ")->execute([
            $domain !== '' ? $domain : null,
            $benutzer !== '' ? $benutzer : null,
            $passwort !== '' ? dv_encrypt($passwort) : null,
            $pin !== '' ? dv_encrypt($pin) : null,
            $datum !== '' ? $datum : null,
            $notizen !== '' ? $notizen : null,
            $id
        ]);

        $pdo->prepare("
            DELETE FROM password_tag_assignments
            WHERE password_id = ?
        ")->execute([$id]);

        if ($newTag !== '') {
            $check = $pdo->prepare("SELECT id FROM password_tags WHERE name = ?");
            $check->execute([$newTag]);
            $existing = $check->fetchColumn();

            if ($existing) {
                $tagsNew[] = (int)$existing;
            } else {
                $pdo->prepare("INSERT INTO password_tags (name) VALUES (?)")
                    ->execute([$newTag]);
                $tagsNew[] = (int)$pdo->lastInsertId();
            }
        }

        foreach (array_unique(array_map('intval', $tagsNew)) as $tid) {
            if ($tid > 0) {
                $pdo->prepare("
                    INSERT IGNORE INTO password_tag_assignments
                    (password_id, tag_id)
                    VALUES (?,?)
                ")->execute([$id, $tid]);
            }
        }

        $pdo->commit();

        if ($action === "save_close") {
            header("Location: index.php");
        } elseif ($action === "save_new") {
            header("Location: add.php");
        } else {
            header("Location: edit.php?id=" . $id);
        }
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Fehler beim Speichern: " . $e->getMessage());
    }
}

/* =====================================================
   DECRYPT
===================================================== */
$plainPw  = dv_decrypt($row["passwort"] ?? null);
$plainPin = dv_decrypt($row["pin"] ?? null);

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title'     => $moduleTitle,
    'show_back' => true,
    'back_url'  => 'index.php'
];
require_once __DIR__ . "/../../core/header_actions.php";
?>

<form method="post" class="card shadow-sm">
<div class="card-body">

<div class="row g-4">

<div class="col-md-6">
<label class="form-label">Domain</label>
<input name="domain" class="form-control"
value="<?= htmlspecialchars((string)($row["domain"] ?? '')) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Benutzer</label>
<input name="benutzer" class="form-control"
value="<?= htmlspecialchars((string)($row["benutzer"] ?? '')) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Passwort</label>
<div class="d-flex gap-2">
<input type="password" id="pwField" name="passwort" class="form-control"
value="<?= htmlspecialchars($plainPw) ?>">

<button type="button" class="btn btn-outline-secondary"
onclick="togglePwField()"
data-bs-toggle="tooltip"
title="Anzeigen">
<i class="bi bi-eye"></i>
</button>

<button type="button" class="btn btn-outline-secondary"
onclick="copyPwField()"
data-bs-toggle="tooltip"
title="Kopieren">
<i class="bi bi-clipboard"></i>
</button>

<button type="button" class="btn btn-outline-warning"
onclick="generatePwField()"
data-bs-toggle="tooltip"
title="Neues Passwort generieren">
<i class="bi bi-key"></i>
</button>
</div>
</div>

<div class="col-md-3">
<label class="form-label">PIN</label>
<input name="pin" class="form-control"
value="<?= htmlspecialchars($plainPin) ?>">
</div>

<div class="col-md-3">
<label class="form-label">Datum</label>
<input type="date" name="datum" class="form-control"
value="<?= htmlspecialchars((string)($row["datum"] ?? '')) ?>">
</div>

<div class="col-12">
<label class="form-label">Notizen</label>
<textarea name="notizen" class="form-control"><?= htmlspecialchars((string)($row["notizen"] ?? '')) ?></textarea>
</div>

<div class="col-12">
<label class="form-label">Tags</label>

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

<input name="new_tag" class="form-control mt-2" placeholder="Neuer Tag">
</div>

<div class="col-12">
<?php
$formConfig = [
'back_url'  => 'index.php',
'important' => true
];
require __DIR__ . "/../../core/form_actions.php";
?>
</div>

</div>
</div>
</form>

<script>
function togglePwField(){const f=document.getElementById("pwField");if(!f)return;f.type=f.type==="password"?"text":"password";}
function copyPwField(){const f=document.getElementById("pwField");if(!f)return;navigator.clipboard.writeText(f.value);}
function generatePwField(){const f=document.getElementById("pwField");if(!f)return;f.value=generatePassword();}
function generatePassword(){const c="ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%";let p="";for(let i=0;i<16;i++){p+=c[Math.floor(Math.random()*c.length)];}return p;}

document.addEventListener("DOMContentLoaded",function(){
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
new bootstrap.Tooltip(el);
});
});
</script>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>