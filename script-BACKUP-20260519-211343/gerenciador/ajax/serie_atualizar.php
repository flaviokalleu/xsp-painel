<?php
// ARQUIVO: /gerenciador/ajax/serie_atualizar.php

header('Content-Type: application/json');

require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Erro: Não foi possível conectar ao banco de dados.']);
    exit;
}

// Recebe todos os dados enviados pelo formulário
$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? null;
$plot = $_POST['plot'] ?? null;
$cover = $_POST['cover'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$release_date = $_POST['release_date'] ?? null;

if (empty($id) || empty($name) || empty($category_id)) {
    echo json_encode(['success' => false, 'message' => 'Erro de validação: ID, Nome e Categoria são obrigatórios.']);
    exit;
}

// SQL para ATUALIZAR (UPDATE) os dados na tabela 'series'
$sql = "UPDATE series SET 
            name = :name, 
            plot = :plot, 
            cover = :cover, 
            category_id = :category_id, 
            release_date = :release_date
        WHERE id = :id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':name' => $name,
        ':plot' => $plot,
        ':cover' => $cover,
        ':category_id' => $category_id,
        ':release_date' => $release_date ?: null
    ]);
    echo json_encode(['success' => true, 'message' => 'Série "' . $name . '" atualizada com sucesso!']);
} catch (PDOException $e) {
    error_log('Erro ao atualizar série: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocorreu um erro no banco de dados. Detalhe: ' . $e->getMessage()]);
}
?>