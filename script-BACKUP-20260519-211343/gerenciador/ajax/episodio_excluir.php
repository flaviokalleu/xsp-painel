<?php
// ARQUIVO: /gerenciador/ajax/episodio_excluir.php (COM DEBUG)
header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

$episode_id = $_POST['id'] ?? null;
if (empty($episode_id)) { echo json_encode(['success' => false, 'message' => 'ID do episódio não fornecido.']); exit; }

try {
    $stmt = $pdo->prepare("DELETE FROM series_episodes WHERE id = :id");
    $stmt->execute([':id' => $episode_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Episódio excluído com sucesso!']);
    } else {
        // MENSAGEM DE ERRO MELHORADA
        echo json_encode(['success' => false, 'message' => 'Nenhum episódio encontrado com o ID fornecido: ' . $episode_id]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>