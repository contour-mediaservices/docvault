<?php
declare(strict_types=1);

session_start();
session_destroy();
?>

<!DOCTYPE html>
<html lang="de">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Abgemeldet – DocVault</title>

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
.logout-wrapper{
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

.logout-card{
    width:340px;
}

.logout-icon{
    font-size:2.2rem;
    color:#D7742C;
    margin-bottom:10px;
}
</style>

<!-- Auto Redirect -->
<meta http-equiv="refresh" content="2;url=/docvault/login.php">

</head>

<body>

<div class="logout-wrapper">

<div class="card shadow-sm logout-card">
<div class="card-body text-center p-4">

<div class="logout-icon">
<i class="bi bi-box-arrow-right"></i>
</div>

<h4 class="fw-semibold mb-2">Abgemeldet</h4>

<p class="text-muted mb-3">
Du wurdest erfolgreich abgemeldet.
</p>

<a href="/docvault/login.php" class="btn btn-dark w-100">
<i class="bi bi-arrow-right-circle"></i>
Erneut anmelden
</a>

</div>
</div>

</div>

</body>
</html>