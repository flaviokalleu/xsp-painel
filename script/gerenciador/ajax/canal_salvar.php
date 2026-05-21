<?php
// ARQUIVO: /gerenciador/ajax/canal_salvar.php

header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

// Recebe os dados do formulário
$name = $_POST['name'] ?? null;
$stream_icon = $_POST['stream_icon'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$epg_channel_id = $_POST['epg_channel_id'] ?? null;
$link = $_POST['link'] ?? null;

// Validação
if (empty($name) || empty($category_id) || empty($link)) {
    echo json_encode(['success' => false, 'message' => 'Erro: Nome, Categoria e URL do Stream são obrigatórios.']);
    exit;
}

// Insere na tabela 'streams' com o tipo 'live'
$sql = "INSERT INTO streams (name, stream_icon, category_id, epg_channel_id, link, stream_type, added) 
        VALUES (:name, :stream_icon, :category_id, :epg_channel_id, :link, 'live', :added)";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':stream_icon' => $stream_icon,
        ':category_id' => $category_id,
        ':epg_channel_id' => $epg_channel_id ?: null, // Permite que seja nulo
        ':link' => $link,
        ':added' => time()
    ]);
    echo json_encode(['success' => true, 'message' => 'Canal "' . $name . '" adicionado com sucesso!']);
} catch (PDOException $e) {
    error_log('Erro ao salvar canal: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>