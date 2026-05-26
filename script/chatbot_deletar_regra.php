<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("chatbot_integrado_funcoes.php"); // Inclui as funções do chatbot

if (!isset($_SESSION['admin_id'])) {
    // Se não estiver logado, não faz nada e redireciona para o login
    header("Location: index.php"); 
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Verifica se o formulário foi enviado com o método POST e se o ID da regra foi passado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rule_id'])) {
    $rule_id = intval($_POST['rule_id']);
    
    // Chama a função para apagar a regra
    if (function_exists('deleteChatBotRule')) {
        deleteChatBotRule($admin_id, $rule_id);
        // Redireciona de volta para a lista com uma mensagem de sucesso
        header("Location: chatbot_regras.php?feedback=success_delete");
        exit;
    }
}

// Se algo der errado (ex: acesso direto ao ficheiro), redireciona de volta para a lista
header("Location: chatbot_regras.php?feedback=error_delete");
exit;
?>
