<?php
// api/kick_all_sessions.php
// Endpoint dedicado para derrubar TODAS as sessões ativas (limpeza total).

// ATENÇÃO: Verifique se o caminho abaixo está correto para o seu ficheiro de conexão com o banco de dados.
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
header('Content-Type: application/json; charset=utf-8');

try {
    $conexao = conectar_bd();
    
    // Comando SQL que deleta TODAS as linhas da tabela 'conexoes'
    $query = "DELETE FROM conexoes"; 
    $statement = $conexao->prepare($query);
    $statement->execute();
    
    $rows_affected = $statement->rowCount();
    
    $message = ($rows_affected > 0) 
        ? "Todas as {$rows_affected} sessões ativas foram derrubadas com sucesso."
        : "Nenhuma sessão ativa encontrada para derrubar.";
            
    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    // Erro de banco de dados
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor (Database Error).']);
} catch (Exception $e) {
    // Outros erros
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}
?>