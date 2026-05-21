<?php
// ARQUIVO: /gerenciador/ajax/episodio_adicionar.php (FINAL)
header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

$series_id = $_POST['series_id'] ?? null;
$season_num = $_POST['season_num'] ?? null;
$episode_num = $_POST['episode_num'] ?? null;
$title = $_POST['title'] ?? null;
$stream_url = $_POST['stream_url'] ?? null;

if (empty($series_id) || empty($season_num) || empty($episode_num) || empty($title) || empty($stream_url)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
    exit;
}

// CORREÇÃO: Usando as colunas corretas 'season', 'title' e 'link'
$sql = "INSERT INTO series_episodes (series_id, season, episode_num, title, link) 
        VALUES (:series_id, :season, :episode_num, :title, :link)";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':series_id' => $series_id,
        ':season' => $season_num,
        ':episode_num' => $episode_num,
        ':title' => $title,
        ':link' => $stream_url
    ]);
    echo json_encode(['success' => true, 'message' => 'Episódio adicionado com sucesso!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>