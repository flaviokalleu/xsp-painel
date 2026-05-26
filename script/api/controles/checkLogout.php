<?php

function redirectLogin($url = './index.php') {
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<script>window.location.href=' . json_encode($url) . ';</script>';
    }
    exit();
}

function destroyCurrentSession() {
    $_SESSION = array();
    session_unset();
    session_destroy();

    if (!headers_sent() && isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

function regenerateSessionSafely() {
    if (headers_sent()) {
        return;
    }

    $now = time();
    $lastRegenerated = (int)($_SESSION['last_regenerated'] ?? 0);
    if ($lastRegenerated === 0 || ($now - $lastRegenerated) > 300) {
        // false = não deleta a sessão antiga; evita race condition com requests AJAX simultâneos
        // que ainda carregam o ID antigo e ficam com sessão vazia → "Sessão inválida"
        session_regenerate_id(false);
        $_SESSION['last_regenerated'] = $now;
    }
}

function checkLogout() {

    if (session_status() !== PHP_SESSION_ACTIVE) {
        redirectLogin();
    }

    if (empty($_SESSION['logged_in_fxtream'])) {
        redirectLogin();
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {

        destroyCurrentSession();

        redirectLogin();
    }

    regenerateSessionSafely();

    $_SESSION['last_activity'] = time();
}

function checkLogoutapi() {
    $resposta = [];

    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['logged_in_fxtream'])) {
        http_response_code(401);
        $resposta['session_expired'] = true;
        $resposta['icon'] = "error";
        $resposta['title'] = "Sessão expirada";
        $resposta['msg'] = "Sua sessão expirou. Redirecionando para o login...";
        $resposta['url'] = "index.php";
        echo json_encode($resposta);
        exit();
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        destroyCurrentSession();
        http_response_code(401);
        $resposta['session_expired'] = true;
        $resposta['icon'] = "error";
        $resposta['title'] = "Sessão expirada";
        $resposta['msg'] = "Sua sessão expirou. Redirecionando para o login...";
        $resposta['url'] = "index.php";
        echo json_encode($resposta);
        exit();
    }
}
