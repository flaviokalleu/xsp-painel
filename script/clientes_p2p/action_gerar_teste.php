<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../api/controles/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
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
    // Busca as configurações do banco de dados
    $stmt_config = $conexao->prepare("SELECT chave, valor FROM configuracoes WHERE chave IN ('p2p_global_password', 'p2p_test_duration_hours')");
    $stmt_config->execute();
    $configs_raw = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $senha_p2p = $configs_raw['p2p_global_password'] ?? null;
    $duracao_teste_horas = $configs_raw['p2p_test_duration_hours'] ?? 4; // Padrão de 4 horas se não encontrar

    if (!$senha_p2p) {
        $_SESSION['mensagem'] = "Erro de configuração: Senha global P2P não encontrada.";
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

    // --- MUDANÇA PRINCIPAL AQUI ---
    // Usa a duração do teste vinda do banco de dados
    $vencimento = date('Y-m-d H:i:s', strtotime("+$duracao_teste_horas hours"));
    $nome_teste = "Teste P2P " . date('d/m H:i');
    
    // Inserir o teste no banco
    $sql = "INSERT INTO clientes (usuario, senha, name, is_p2p, Criado_em, Vencimento, conexoes, admin_id, plano) 
            VALUES (:usuario, :senha, :name, 1, NOW(), :vencimento, 1, 1, 'Teste P2P')";
            
    $stmt_insert = $conexao->prepare($sql);
    
    $stmt_insert->bindParam(':usuario', $codigo_usuario);
    $stmt_insert->bindParam(':senha', $senha_p2p);
    $stmt_insert->bindParam(':name', $nome_teste);
    $stmt_insert->bindParam(':vencimento', $vencimento);

    if ($stmt_insert->execute()) {
        $_SESSION['mensagem'] = "Teste P2P de $duracao_teste_horas horas gerado! Código: <strong>$codigo_usuario</strong>";
        $_SESSION['msg_type'] = "alert-success";
    } else {
        $_SESSION['mensagem'] = "Erro ao gerar o teste.";
        $_SESSION['msg_type'] = "alert-error";
    }

} catch (PDOException $e) {
    $_SESSION['mensagem'] = "Erro de banco de dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "alert-error";
}

header('Location: index.php');
exit();