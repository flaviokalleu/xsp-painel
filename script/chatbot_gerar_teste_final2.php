<?php
// chatbot_gerar_teste_final.php
// Versão final e 100% integrada com as funções do seu painel.

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

require_once('./api/controles/db.php');
require_once('./api/controles/testes.php'); // Inclui o arquivo com a função de criar teste

// CONFIGURAÇÕES DO TESTE AUTOMÁTICO
$plano_padrao_para_teste = 1; 
$duracao_padrao_horas = 3;    
$dominio_servidor = "http://" . $_SERVER['HTTP_HOST'];

// **INÍCIO DA MUDANÇA**
// Pega o ID e o TOKEN do admin enviados pela API
$admin_id = $_GET['admin_id'] ?? null;
$admin_token = $_GET['token'] ?? null;

if (empty($admin_id) || empty($admin_token)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: Credenciais do administrador nao foram fornecidas.']);
    exit;
}

// SIMULA UMA SESSÃO DE LOGIN VÁLIDA PARA O PAINEL
$_SESSION['admin_id'] = $admin_id;
$_SESSION['token'] = $admin_token;
// **FIM DA MUDANÇA**

// Gera dados aleatórios para o novo teste
$usuario_teste = strtolower(substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8));
$senha_teste = strtolower(substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 6));

// Chama a função NATIVA do seu painel para criar o teste
$resultado_criacao = confirme_adicionar_testes(
    'Teste via Chatbot',   // name
    $usuario_teste,        // usuario
    $senha_teste,          // senha
    0,                     // adulto
    $plano_padrao_para_teste, // plano
    'N/A',                 // Dispositivo
    'N/A',                 // App
    'PIX',                 // Forma_de_pagamento
    'Cliente Chatbot',     // nome_do_pagador
    '',                    // Whatsapp
    null,                  // indicacao
    null,                  // mac
    null,                  // key
    $duracao_padrao_horas  // tempo
);

if (isset($resultado_criacao['icon']) && $resultado_criacao['icon'] === 'success') {
    
    // Se deu certo, monta a resposta JSON para o chatbot
    $vencimento = date('d/m/Y H:i', strtotime("+$duracao_padrao_horas hours"));
    $link_m3u_encurtado = "{$dominio_servidor}/t/" . base64_encode($usuario_teste);
    $link_ssiptv_encurtado = "{$dominio_servidor}/e/" . base64_encode($senha_teste);
    
    $dados_finais = [
        'sucesso'       => true,
        'usuario'       => $usuario_teste,
        'senha'         => $senha_teste,
        'vencimento'    => $vencimento,
        'link_m3u_encurtado' => $link_m3u_encurtado,
        'link_ssiptv_encurtado' => $link_ssiptv_encurtado
    ];
    
    echo json_encode($dados_finais);

} else {
    // Se a função do painel deu erro, repassa o erro
    echo json_encode(['sucesso' => false, 'mensagem' => $resultado_criacao['msg'] ?? 'Erro desconhecido ao criar teste.']);
}
?>