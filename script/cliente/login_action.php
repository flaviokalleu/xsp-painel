<?php
session_start();
require_once('../api/controles/db.php'); // O caminho sobe um nível

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$usuario = $_POST['usuario'];
$senha = $_POST['senha'];

$conexao = conectar_bd();
if (!$conexao) {
    $_SESSION['login_error'] = 'Erro de sistema. Tente novamente mais tarde.';
    header('Location: index.php');
    exit();
}

// ATENÇÃO: Esta verificação de senha é em texto plano. Funciona com a estrutura atual, mas não é o ideal para segurança.
$stmt = $conexao->prepare("SELECT id, usuario FROM clientes WHERE usuario = ? AND senha = ?");
$stmt->execute([$usuario, $senha]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cliente) {
    // Login bem-sucedido
    $_SESSION['client_loggedin'] = true;
    $_SESSION['client_id'] = $cliente['id'];
    $_SESSION['client_username'] = $cliente['usuario'];
    header('Location: dashboard.php');
    exit();
} else {
    // Login falhou
    $_SESSION['login_error'] = 'Usuário ou senha inválidos.';
    header('Location: index.php');
    exit();
}