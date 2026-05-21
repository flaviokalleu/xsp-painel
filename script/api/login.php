<?php
// Define o cabeçalho para retornar JSON
header('Content-Type: application/json; charset=utf-8');

// ======================================================================
//  CORREÇÃO: O caminho para os ficheiros de controle foi ajustado.
//  Agora ele procura pela pasta 'controles' dentro da pasta 'api'.
// ======================================================================
require_once('controles/db.php'); 
require_once('controles/login.php');

// Pega o usuário e a senha enviados pelo formulário
$username = isset($_POST['username']) ? $_POST['username'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Verifica se a função de login existe no arquivo de controle
if (function_exists('login')) {
    // Chama a sua função de login original e guarda a resposta
    $resposta = login($username, $password);
} else {
    // Se a função não for encontrada, cria uma mensagem de erro
    $resposta = [
        'title' => 'Erro crítico: Função de login não encontrada no servidor.',
        'icon' => 'error'
    ];
}

// Envia a resposta de volta para a página de login em formato JSON
echo json_encode($resposta);
?>
