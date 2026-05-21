<?php
session_start();
require_once './api/controles/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: testes_p2p.php');
    exit();
}

$conexao = conectar_bd();
if (!$conexao) {
    $_SESSION['mensagem'] = "Erro fatal: Não foi possível conectar ao banco de dados.";
    $_SESSION['msg_type'] = "alert-error";
    header('Location: testes_p2p.php');
    exit();
}

try {
    // 1. Busca a senha e a duração do teste do banco de dados
    $stmt_senha = $conexao->prepare("SELECT setting_value FROM settings WHERE setting_name = 'p2p_default_password' LIMIT 1");
    $stmt_senha->execute();
    $senha_p2p = $stmt_senha->fetchColumn();

    $stmt_duracao = $conexao->prepare("SELECT valor FROM configuracoes WHERE chave = 'p2p_test_duration_hours' LIMIT 1");
    $stmt_duracao->execute();
    $duracao_teste_horas = $stmt_duracao->fetchColumn() ?? 4; // Padrão de 4 horas se não encontrar

    if (!$senha_p2p) {
        $_SESSION['mensagem'] = "Erro Crítico: A senha padrão P2P não foi encontrada na tabela 'settings'.";
        $_SESSION['msg_type'] = "alert-error";
        header('Location: testes_p2p.php');
        exit();
    }

    // 2. Gerar um código de usuário único
    do {
        $codigo_usuario = mt_rand(10000000, 99999999);
        $stmt_check = $conexao->prepare("SELECT id FROM clientes WHERE usuario = :usuario");
        $stmt_check->bindParam(':usuario', $codigo_usuario);
        $stmt_check->execute();
    } while ($stmt_check->fetchColumn());

    // 3. Calcular vencimento
    $vencimento = date('Y-m-d H:i:s', strtotime("+$duracao_teste_horas hours"));
    $nome_teste = "Teste P2P " . date('d/m H:i');
    
    // 4. Pega o ID do criador (admin ou revendedor)
    $creator_id = $_SESSION['admin_id'];

    // 5. Prepara e executa a inserção no banco
    $sql = "INSERT INTO clientes (usuario, senha, name, is_p2p, Criado_em, Vencimento, conexoes, admin_id, plano) 
            VALUES (:usuario, :senha, :name, 1, NOW(), :vencimento, 1, :admin_id, 'Teste P2P')";
            
    $stmt_insert = $conexao->prepare($sql);
    
    $stmt_insert->bindParam(':usuario', $codigo_usuario);
    $stmt_insert->bindParam(':senha', $senha_p2p);
    $stmt_insert->bindParam(':name', $nome_teste);
    $stmt_insert->bindParam(':vencimento', $vencimento);
    $stmt_insert->bindParam(':admin_id', $creator_id);

    if ($stmt_insert->execute()) {
        // Busca o template de mensagem no banco
        $stmt_template = $conexao->prepare("SELECT valor FROM configuracoes WHERE chave = 'p2p_message_template' LIMIT 1");
        $stmt_template->execute();
        $template = $stmt_template->fetchColumn();
        
        // Prepara as variáveis para substituição
        $vencimento_formatado = date('d/m/Y H:i', strtotime($vencimento));
        $placeholders = ['#cliente#', '#codigo#', '#vencimento#'];
        $values = [$nome_teste, $codigo_usuario, $vencimento_formatado];

        // Substitui as variáveis e cria a mensagem final
        $mensagem_final = str_replace($placeholders, $values, $template);
        
        // Salva a mensagem na sessão para exibir o modal na próxima página
        $_SESSION['show_p2p_modal_message'] = $mensagem_final;
        $_SESSION['show_p2p_modal_title'] = '✅ Teste P2P Gerado!';

    } else {
        $_SESSION['mensagem'] = "Erro ao gerar o teste.";
        $_SESSION['msg_type'] = "alert-error";
    }

} catch (PDOException $e) {
    $_SESSION['mensagem'] = "Erro de banco de dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "alert-error";
}

header('Location: testes_p2p.php');
exit();