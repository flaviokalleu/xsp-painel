<?php
// ARQUIVO: /gerenciador/ajax/filme_atualizar.php (NOVO PAINEL)
header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? null;
$stream_icon = $_POST['stream_icon'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$link = $_POST['link'] ?? null;
$tmdb_id = $_POST['tmdb_id'] ?? null;
$plot = $_POST['plot'] ?? null;
$year = $_POST['year'] ?? null;

if (empty($id) || empty($name) || empty($category_id) || empty($link)) {
    echo json_encode(['success' => false, 'message' => 'ID, Nome, Categoria e URL são obrigatórios.']);
    exit;
}

$sql = "UPDATE streams SET 
            name = :name, stream_icon = :stream_icon, category_id = :category_id, link = :link, 
            tmdb_id = :tmdb_id, plot = :plot, year = :year
        WHERE id = :id AND stream_type = 'movie'";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':name' => $name,
        ':stream_icon' => $stream_icon,
        ':category_id' => $category_id,
        ':link' => $link,
        ':tmdb_id' => $tmdb_id ?: null,
        ':plot' => $plot,
        ':year' => $year ?: null
    ]);
    echo json_encode(['success' => true, 'message' => 'Filme "' . $name . '" atualizado com sucesso!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>