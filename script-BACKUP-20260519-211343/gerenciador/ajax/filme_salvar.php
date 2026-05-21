<?php
// ARQUIVO: /gerenciador/ajax/filme_salvar.php (NOVO PAINEL)
header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

$name = $_POST['name'] ?? null;
$stream_icon = $_POST['stream_icon'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$link = $_POST['link'] ?? null;
$tmdb_id = $_POST['tmdb_id'] ?? null;
$plot = $_POST['plot'] ?? null;
$year = $_POST['year'] ?? null;

if (empty($name) || empty($category_id) || empty($link)) {
    echo json_encode(['success' => false, 'message' => 'Nome, Categoria e URL do Stream são obrigatórios.']);
    exit;
}

$sql = "INSERT INTO streams (name, stream_icon, category_id, link, tmdb_id, plot, year, stream_type, added) 
        VALUES (:name, :stream_icon, :category_id, :link, :tmdb_id, :plot, :year, 'movie', :added)";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':stream_icon' => $stream_icon,
        ':category_id' => $category_id,
        ':link' => $link,
        ':tmdb_id' => $tmdb_id ?: null,
        ':plot' => $plot,
        ':year' => $year ?: null,
        ':added' => time()
    ]);
    echo json_encode(['success' => true, 'message' => 'Filme "' . $name . '" adicionado com sucesso!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>