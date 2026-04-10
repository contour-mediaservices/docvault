<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'hosting';

/* =====================================================
   ID
===================================================== */
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    header("Location: /docvault/modules/hosting/index.php");
    exit;
}

/* =====================================================
   DATENSATZ
===================================================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM hosting_services
    WHERE id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: /docvault/modules/hosting/index.php");
    exit;
}

/* =====================================================
   SERVICE-TYPEN
===================================================== */
$services = $pdo->query("
    SELECT id, name
    FROM hosting_service_types
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   AKTUELLE SERVICES
===================================================== */
$stmt = $pdo->prepare("
    SELECT service_type_id
    FROM hosting_service_assignments
    WHERE hosting_id = ?
");
$stmt->execute([$id]);
$currentServiceIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

/* =====================================================
   POST
===================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "save";

    $domain        = trim((string)($_POST["domain"] ?? ""));
    $customer      = trim((string)($_POST["customer"] ?? ""));
    $billingDate   = trim((string)($_POST["billing_date"] ?? ""));
    $billingCycle  = trim((string)($_POST["billing_cycle"] ?? "yearly"));
    $notes         = trim((string)($_POST["notes"] ?? ""));
    $selectedTypes = $_POST["services"] ?? [];

    if ($domain !== "") {

        try {
            $pdo->beginTransaction();

            /* UPDATE */
            $pdo->prepare("
                UPDATE hosting_services
                SET domain=?, customer=?, billing_date=?, billing_cycle=?, notes=?
                WHERE id=?
            ")->execute([
                $domain,
                $customer,
                $billingDate ?: null,
                $billingCycle ?: 'yearly',
                $notes,
                $id
            ]);

            /* SERVICES RESET */
            $pdo->prepare("
                DELETE FROM hosting_service_assignments
                WHERE hosting_id=?
            ")->execute([$id]);

            /* SERVICES NEU */
            if (!empty($selectedTypes)) {

                $ins = $pdo->prepare("
                    INSERT INTO hosting_service_assignments
                    (hosting_id, service_type_id)
                    VALUES (?,?)
                ");

                foreach (array_unique(array_map('intval', $selectedTypes)) as $sid) {
                    if ($sid > 0) {
                        $ins->execute([$id, $sid]);
                    }
                }
            }

            $pdo->commit();

            if ($action === "save_close") {
                header("Location: /docvault/modules/hosting/index.php");
            } elseif ($action === "save_new") {
                header("Location: /docvault/modules/hosting/add.php");
            } else {
                header("Location: /docvault/modules/hosting/edit.php?id=".$id."&saved=1");
            }
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header("Location: /docvault/modules/hosting/edit.php?id=".$id."&error=1");
            exit;
        }
    } else {
        header("Location: /docvault/modules/hosting/edit.php?id=".$id."&error=1");
        exit;
    }
}

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title'     => 'Hosting bearbeiten',
    'show_back' => true,
    'back_url'  => '/docvault/modules/hosting/index.php'
];

require_once __DIR__ . "/../../core/header_actions.php";
?>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success">Eintrag gespeichert.</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger">Fehler beim Speichern.</div>
<?php endif; ?>

<form method="post" class="card shadow-sm">
<div class="card-body">

<div class="row g-4">

<div class="col-md-6">
<label class="form-label">Domain</label>
<input type="text"
       name="domain"
       class="form-control"
       value="<?= htmlspecialchars((string)$row["domain"]) ?>"
       required>
</div>

<div class="col-md-6">
<label class="form-label">Kunde</label>
<input type="text"
       name="customer"
       class="form-control"
       value="<?= htmlspecialchars((string)($row["customer"] ?? "")) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Abrechnungsdatum</label>
<input type="date"
       name="billing_date"
       class="form-control"
       value="<?= htmlspecialchars((string)($row["billing_date"] ?? "")) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Zyklus</label>
<select name="billing_cycle" class="form-select">
    <option value="monthly" <?= ($row["billing_cycle"] ?? '') === 'monthly' ? 'selected' : '' ?>>monatlich</option>
    <option value="yearly" <?= ($row["billing_cycle"] ?? 'yearly') === 'yearly' ? 'selected' : '' ?>>jährlich</option>
</select>
</div>

<div class="col-12">
<label class="form-label">Leistungen</label>

<div class="d-flex flex-wrap gap-2">
<?php foreach ($services as $s): ?>
<label class="badge bg-light text-dark border">
<input type="checkbox"
       name="services[]"
       value="<?= (int)$s["id"] ?>"
       <?= in_array((int)$s["id"], $currentServiceIds, true) ? 'checked' : '' ?>>
<?= htmlspecialchars((string)$s["name"]) ?>
</label>
<?php endforeach; ?>
</div>

</div>

<div class="col-12">
<label class="form-label">Notizen</label>
<textarea name="notes"
          class="form-control"
          rows="4"><?= htmlspecialchars((string)($row["notes"] ?? "")) ?></textarea>
</div>

<div class="col-12">
<?php
$formConfig = [
    'back_url' => '/docvault/modules/hosting/index.php',
    'important' => true
];
require __DIR__ . "/../../core/form_actions.php";
?>
</div>

</div>

</div>
</form>

<?php require_once __DIR__ . "/../../core/layout_end.php"; ?>