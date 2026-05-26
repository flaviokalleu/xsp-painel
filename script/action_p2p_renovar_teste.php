<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once './api/controles/db.php';

$id_teste = $_GET['id'] ?? null;

if (!$id_teste) {
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
    // Busca a duração padrão dos testes nas configurações
    $stmt_duracao = $conexao->prepare("SELECT valor FROM configuracoes WHERE chave = 'p2p_test_duration_hours' LIMIT 1");
    $stmt_duracao->execute();
    $duracao_teste_horas = $stmt_duracao->fetchColumn() ?? 4; // Padrão de 4 horas se não encontrar

    // Prepara a query de atualização com segurança
    $sql = "UPDATE clientes 
            SET Vencimento = DATE_ADD(GREATEST(Vencimento, NOW()), INTERVAL :duracao HOUR)
            WHERE id = :id AND plano = 'Teste P2P'"; // Garante que só está renovando um teste
            
    $params = [
        ':duracao' => $duracao_teste_horas,
        ':id'      => $id_teste
    ];
    
    // Se for revendedor, adiciona a verificação de segurança
    if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0) {
        $sql .= " AND admin_id = :admin_id";
        $params[':admin_id'] = $_SESSION['admin_id'];
    }
            
    $stmt = $conexao->prepare($sql);
    
    if ($stmt->execute($params) && $stmt->rowCount() > 0) {
        $_SESSION['mensagem'] = "Teste estendido por mais $duracao_teste_horas horas com sucesso!";
        $_SESSION['msg_type'] = "alert-success";
    } else {
        $_SESSION['mensagem'] = "Erro: Teste não encontrado ou você não tem permissão para renová-lo.";
        $_SESSION['msg_type'] = "alert-error";
    }

} catch (PDOException $e) {
    $_SESSION['mensagem'] = "Erro de banco de dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "alert-error";
}

header('Location: testes_p2p.php');
exit();