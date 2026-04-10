<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../config.php";

/* =====================================================
   CORE
===================================================== */
$activeModule = 'hosting';

/* =====================================================
   POST
===================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "save";
    $name   = trim((string)($_POST["name"] ?? ""));

    if ($name !== "") {

        try {
            $pdo->prepare("
                INSERT INTO hosting_service_types (name)
                VALUES (?)
            ")->execute([$name]);

            $id = (int)$pdo->lastInsertId();

            if ($action === "save_close") {
                header("Location: /docvault/modules/hosting/service_types/index.php");
            } elseif ($action === "save_new") {
                header("Location: /docvault/modules/hosting/service_types/add.php");
            } else {
                header("Location: /docvault/modules/hosting/service_types/edit.php?id=" . $id);
            }
            exit;

        } catch (Throwable $e) {
            header("Location: /docvault/modules/hosting/service_types/add.php?error=1");
            exit;
        }
    } else {
        header("Location: /docvault/modules/hosting/service_types/add.php?error=1");
        exit;
    }
}

/* =====================================================
   LAYOUT
===================================================== */
require_once __DIR__ . "/../../../core/module_layout_start.php";

/* =====================================================
   HEADER
===================================================== */
$headerConfig = [
    'title'     => 'Leistung anlegen',
    'show_back' => true,
    'back_url'  => '/docvault/modules/hosting/service_types/index.php'
];

require_once __DIR__ . "/../../../core/header_actions.php";
?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger">
Bitte Bezeichnung eingeben.
</div>
<?php endif; ?>

<form method="post" class="card shadow-sm">
<div class="card-body">

<div class="row g-4">

<div class="col-12">
<label class="form-label">Bezeichnung</label>
<input type="text"
       name="name"
       class="form-control"
       required>
</div>

<div class="col-12">
<?php
$formConfig = [
    'back_url'  => '/docvault/modules/hosting/service_types/index.php',
    'important' => true
];
require __DIR__ . "/../../../core/form_actions.php";
?>
</div>

</div>

</div>
</form>

<?php require_once __DIR__ . "/../../../core/layout_end.php"; ?>