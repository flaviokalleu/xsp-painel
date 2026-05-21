<?php
// ARQUIVO: /gerenciador/ajax/series_excluir_massa.php

header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

$ids = $_POST['ids'] ?? null;

if (empty($ids) || !is_array($ids)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum ID foi fornecido.']);
    exit;
}
$ids = array_filter($ids, 'is_numeric');
if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum ID válido foi fornecido.']);
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $pdo->beginTransaction();

    // 1. Exclui os episódios das séries selecionadas
    $sql_episodes = "DELETE FROM series_episodes WHERE series_id IN ($placeholders)";
    $stmt_episodes = $pdo->prepare($sql_episodes);
    $stmt_episodes->execute($ids);

    // 2. Exclui as séries principais
    $sql_series = "DELETE FROM series WHERE id IN ($placeholders)";
    $stmt_series = $pdo->prepare($sql_series);
    $stmt_series->execute($ids);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => $stmt_series->rowCount() . ' série(s) e seus episódios foram excluído(s) com sucesso!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>