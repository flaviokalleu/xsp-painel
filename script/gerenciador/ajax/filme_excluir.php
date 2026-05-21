<?php
// ARQUIVO: /gerenciador/ajax/filme_excluir.php (NOVO PAINEL - CORRIGIDO)
header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

$id = $_POST['id'] ?? null;
if (empty($id)) { echo json_encode(['success' => false, 'message' => 'ID não fornecido.']); exit; }

try {
    // CORREÇÃO: Apaga da tabela 'streams' ONDE o ID corresponde E o tipo é 'movie'
    $stmt = $pdo->prepare("DELETE FROM streams WHERE id = :id AND stream_type = 'movie'");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Filme excluído com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum filme encontrado com o ID fornecido.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>