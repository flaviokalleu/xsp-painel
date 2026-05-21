<?php
// api/kick_session.php
// Endpoint para derrubar uma sessão manualmente
// ATENÇÃO: Verifique se o caminho abaixo está correto para o seu ficheiro de conexão com o banco de dados.
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
header('Content-Type: application/json; charset=utf-8');

// 1. Obtém e valida o ID da sessão da URL
$session_id = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'ID de sessão inválido.']);
    exit;
}

try {
    $conexao = conectar_bd();
    
    // 2. Comando SQL para DELETAR a sessão específica
    $query = "DELETE FROM conexoes WHERE id = :session_id";
    $statement = $conexao->prepare($query);
    $statement->bindParam(':session_id', $session_id);
    $statement->execute();
    
    $rows_affected = $statement->rowCount();
    
    if ($rows_affected > 0) {
        echo json_encode(['success' => true, 'message' => "Sessão ID {$session_id} derrubada."]);
    } else {
        echo json_encode(['success' => false, 'message' => "Sessão ID {$session_id} não encontrada ou já estava inativa."]);
    }
    
} catch (PDOException $e) {
    error_log("ERRO AO DERRUBAR SESSÃO: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro de banco de dados.']);
}
?>