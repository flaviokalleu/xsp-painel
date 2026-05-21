<?php
// ARQUIVO: /gerenciador/ajax/episodio_atualizar.php (VERSÃO FINAL E CORRIGIDA)
header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco de dados.']); exit; }

// Recebe os dados do formulário de edição via POST
$id = $_POST['id'] ?? null;
$season_num = $_POST['season_num'] ?? null;
$episode_num = $_POST['episode_num'] ?? null;
$title = $_POST['title'] ?? null;
$stream_url = $_POST['stream_url'] ?? null;

// Validação para garantir que nenhum campo essencial está vazio
if (empty($id) || empty($season_num) || empty($episode_num) || empty($title) || empty($stream_url)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
    exit;
}

// Comando SQL para ATUALIZAR (UPDATE) o episódio na tabela 'series_episodes'
// Usando os nomes de coluna corretos que vimos na sua estrutura: season, episode_num, title, link
$sql = "UPDATE series_episodes SET 
            season = :season, 
            episode_num = :episode_num, 
            title = :title, 
            link = :link 
        WHERE id = :id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':season' => $season_num,
        ':episode_num' => $episode_num,
        ':title' => $title,
        ':link' => $stream_url
    ]);
    echo json_encode(['success' => true, 'message' => 'Episódio atualizado com sucesso!']);
} catch (PDOException $e) {
    // Se der erro, esta mensagem é mais detalhada e nos ajuda a encontrar o problema
    error_log('Erro ao atualizar episódio: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>