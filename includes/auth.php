<?php
function requireLogin(): void
{
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
        session_unset();
        session_destroy();
        header('Location: login.php?exp=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function requireGrupo(int ...$grupos): void
{
    requireLogin();
    if (!in_array($_SESSION['grupo'], $grupos)) {
        header('Location: login.php?denied=1');
        exit;
    }
}