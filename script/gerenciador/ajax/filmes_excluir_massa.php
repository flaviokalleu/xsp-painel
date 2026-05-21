<?php
// ARQUIVO: /gerenciador/ajax/filmes_excluir_massa.php

header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

// Recebe a lista de IDs. Espera um array.
$ids = $_POST['ids'] ?? null;

if (empty($ids) || !is_array($ids)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum ID foi fornecido ou o formato é inválido.']);
    exit;
}

// Garante que todos os IDs são números inteiros para segurança
$ids = array_filter($ids, 'is_numeric');

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum ID válido foi fornecido.']);
    exit;
}

try {
    // Cria os placeholders (?) para a cláusula IN. Ex: (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Deleta todos os filmes cujo ID está na lista fornecida
    $sql = "DELETE FROM filmes WHERE id IN ($placeholders)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);

    echo json_encode(['success' => true, 'message' => $stmt->rowCount() . ' filme(s) excluído(s) com sucesso!']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>