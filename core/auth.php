<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['docvault_auth']) || $_SESSION['docvault_auth'] !== true) {
    header("Location: /docvault/login.php");
    exit;
}