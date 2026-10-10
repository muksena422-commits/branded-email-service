<?php
session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['admin_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}