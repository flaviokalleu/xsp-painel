<?php
ob_start();
header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Erro DB']); exit; }

$ids = $_POST['ids'] ?? null;
if (empty($ids) || !is_array($ids)) { echo json_encode(['success' => false, 'message' => 'Nenhum ID fornecido.']); exit; }
$ids = array_filter($ids, 'is_numeric');
if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'Nenhum ID válido fornecido.']); exit; }

try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    // Deleta apenas streams do tipo 'live' para segurança
    $sql = "DELETE FROM streams WHERE stream_type = 'live' AND id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    echo json_encode(['success' => true, 'message' => $stmt->rowCount() . ' canal(is) excluído(s) com sucesso!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>