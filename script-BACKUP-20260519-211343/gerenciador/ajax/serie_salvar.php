<?php
// ARQUIVO: /gerenciador/ajax/serie_salvar.php

header('Content-Type: application/json');

require_once('../../api/controles/db.php');
$pdo = conectar_bd();

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Erro: Não foi possível conectar ao banco de dados.']);
    exit;
}

// Recebe dados do formulário e insere na tabela 'series'
$name = $_POST['name'] ?? null;
$plot = $_POST['plot'] ?? null;
$cover = $_POST['cover'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$release_date = $_POST['release_date'] ?? null;

// Validação
if (empty($name) || empty($category_id)) {
    echo json_encode(['success' => false, 'message' => 'Erro de validação: Nome e Categoria são obrigatórios.']);
    exit;
}

// As colunas (name, plot, cover, etc.) devem bater com a sua tabela 'series'
$sql = "INSERT INTO series (name, plot, cover, category_id, release_date) 
        VALUES (:name, :plot, :cover, :category_id, :release_date)";

try {
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':name' => $name,
        ':plot' => $plot,
        ':cover' => $cover,
        ':category_id' => $category_id,
        ':release_date' => $release_date ?: null
    ]);

    echo json_encode(['success' => true, 'message' => 'Série "' . $name . '" adicionada com sucesso!']);

} catch (PDOException $e) {
    error_log('Erro ao salvar série: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocorreu um erro no banco de dados. Detalhe: ' . $e->getMessage()]);
}

?>