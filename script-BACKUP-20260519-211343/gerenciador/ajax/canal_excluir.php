<?php
// ARQUIVO: /gerenciador/ajax/canal_excluir.php

header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

$id = $_POST['id'] ?? null;
if (empty($id)) { echo json_encode(['success' => false, 'message' => 'ID não fornecido.']); exit; }

try {
    // Apaga o stream da tabela ONDE o ID corresponde E o tipo é 'live' (segurança extra)
    $stmt = $pdo->prepare("DELETE FROM streams WHERE id = :id AND stream_type = 'live'");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Canal excluído com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum canal encontrado com o ID fornecido.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>