<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../api/controles/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Usando a versão que não precisa da coluna 'duracao_dias' nos planos
$name = trim($_POST['name'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$plano_id = $_POST['plano_id'] ?? null;
$plano_duracao = 30; // Define uma duração padrão de 30 dias

if (empty($name) || empty($plano_id)) {
    $_SESSION['mensagem'] = "Erro: Nome e Plano são obrigatórios.";
    $_SESSION['msg_type'] = "alert-error";
    header('Location: criar_usuario.php');
    exit();
}

$conexao = conectar_bd();
if (!$conexao) {
    $_SESSION['mensagem'] = "Erro fatal: Não foi possível conectar ao banco de dados.";
    $_SESSION['msg_type'] = "alert-error";
    header('Location: index.php');
    exit();
}

try {
    // --- CORREÇÃO FINAL APLICADA AQUI ---
    // Obter a senha P2P global usando as colunas corretas 'chave' e 'valor'
    $stmt_senha = $conexao->prepare("SELECT valor FROM configuracoes WHERE chave = 'p2p_global_password' LIMIT 1");
    $stmt_senha->execute();
    $senha_p2p = $stmt_senha->fetchColumn();

    if (!$senha_p2p) {
        $_SESSION['mensagem'] = "Erro de configuração: A senha global com a chave 'p2p_global_password' não foi encontrada na tabela 'configuracoes'. Verifique no phpMyAdmin.";
        $_SESSION['msg_type'] = "alert-error";
        header('Location: index.php');
        exit();
    }

    // Gerar um código de usuário único
    do {
        $codigo_usuario = mt_rand(10000000, 99999999);
        $stmt_check = $conexao->prepare("SELECT id FROM clientes WHERE usuario = :usuario");
        $stmt_check->bindParam(':usuario', $codigo_usuario);
        $stmt_check->execute();
    } while ($stmt_check->fetchColumn());

    // Calcular data de vencimento
    $vencimento = date('Y-m-d H:i:s', strtotime("+$plano_duracao days"));
    
    // Inserir o novo usuário no banco
    $sql = "INSERT INTO clientes (usuario, senha, name, whatsapp, plano, is_p2p, Criado_em, Vencimento, conexoes, admin_id) 
            VALUES (:usuario, :senha, :name, :whatsapp, :plano, 1, NOW(), :vencimento, 1, 1)";
            
    $stmt_insert = $conexao->prepare($sql);
    
    $stmt_insert->bindParam(':usuario', $codigo_usuario);
    $stmt_insert->bindParam(':senha', $senha_p2p);
    $stmt_insert->bindParam(':name', $name);
    $stmt_insert->bindParam(':whatsapp', $whatsapp);
    $stmt_insert->bindParam(':plano', $plano_id);
    $stmt_insert->bindParam(':vencimento', $vencimento);

    if ($stmt_insert->execute()) {
        $_SESSION['mensagem'] = "Usuário P2P criado com sucesso! Código: <strong>$codigo_usuario</strong>";
        $_SESSION['msg_type'] = "alert-success";
    } else {
        $_SESSION['mensagem'] = "Erro ao criar usuário.";
        $_SESSION['msg_type'] = "alert-error";
    }

} catch (PDOException $e) {
    // Mensagem de erro mais detalhada para debug
    $_SESSION['mensagem'] = "Erro de banco de dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "alert-error";
}

header('Location: index.php');
exit();