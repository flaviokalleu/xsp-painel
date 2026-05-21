<?php
// ARQUIVO: /gerenciador/ajax/canal_atualizar.php

header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

// Recebe os dados do formulário
$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? null;
$stream_icon = $_POST['stream_icon'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$epg_channel_id = $_POST['epg_channel_id'] ?? null;
$link = $_POST['link'] ?? null;

if (empty($id) || empty($name) || empty($category_id) || empty($link)) {
    echo json_encode(['success' => false, 'message' => 'ID, Nome, Categoria e URL do Stream são obrigatórios.']);
    exit;
}

// SQL para ATUALIZAR (UPDATE) os dados
$sql = "UPDATE streams SET 
            name = :name, 
            stream_icon = :stream_icon, 
            category_id = :category_id, 
            epg_channel_id = :epg_channel_id, 
            link = :link 
        WHERE id = :id AND stream_type = 'live'";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':name' => $name,
        ':stream_icon' => $stream_icon,
        ':category_id' => $category_id,
        ':epg_channel_id' => $epg_channel_id ?: null,
        ':link' => $link
    ]);
    echo json_encode(['success' => true, 'message' => 'Canal "' . $name . '" atualizado com sucesso!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>