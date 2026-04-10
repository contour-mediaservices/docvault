<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . "/config.php";

$error = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pin = $_POST["pin"] ?? "";

    if ($pin === DOCVAULT_PIN) {
        $_SESSION['docvault_auth'] = true;
        header("Location: /docvault/");
        exit;
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>DocVault Login</title>

<!-- Bootstrap -->
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<!-- Icons -->
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- Dein CSS -->
<link rel="stylesheet"
href="/docvault/css/docvault.css">

<style>
/* Login spezifisch */

.login-wrapper{
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

.login-card{
    width:340px;
}

.login-title{
    font-weight:600;
    margin-bottom:10px;
}

.login-icon{
    font-size:2.2rem;
    color:#D7742C;
    margin-bottom:10px;
}

.login-input{
    text-align:center;
    font-size:1.3rem;
    letter-spacing:4px;
}
</style>

</head>


<body>

<div class="login-wrapper">

<div class="card shadow-sm login-card">

<div class="card-body text-center p-4">

<div class="login-icon">
<i class="bi bi-shield-lock"></i>
</div>

<h4 class="login-title">DocVault</h4>

<p class="text-muted mb-3">Bitte PIN eingeben</p>

<form method="post">

<input type="password"
       name="pin"
       class="form-control login-input mb-3"
       placeholder="••••"
       inputmode="numeric"
       pattern="[0-9]*"
       autofocus>

<?php if ($error): ?>
<div class="text-danger mb-2">
Falsche PIN
</div>
<?php endif; ?>

<button class="btn btn-dark w-100">
<i class="bi bi-unlock"></i>
Login
</button>

</form>

</div>

</div>

</div>

</body>
</html>