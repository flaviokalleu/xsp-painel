<?php
// Endpoint para derrubar uma sessão manualmente.
// Admin derruba qualquer sessão; revendedor só derruba sessões dos seus clientes.
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
header('Content-Type: application/json; charset=utf-8');

$nivel = $_SESSION['nivel_admin'] ?? -1;
if ($nivel !== 1 && $nivel !== 0) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

$session_id = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'ID de sessão inválido.']);
    exit;
}

try {
    $conexao = conectar_bd();

    if ($nivel === 0) {
        // Revendedor: deleta só se a sessão pertence a um cliente dele
        $admin_id = (int)($_SESSION['admin_id'] ?? 0);
        $query = "DELETE con FROM conexoes con
                  INNER JOIN clientes cl ON cl.usuario = con.usuario AND cl.admin_id = :admin_id
                  WHERE con.id = :session_id";
        $stmt = $conexao->prepare($query);
        $stmt->bindParam(':admin_id',   $admin_id,   PDO::PARAM_INT);
        $stmt->bindParam(':session_id', $session_id, PDO::PARAM_INT);
    } else {
        $query = "DELETE FROM conexoes WHERE id = :session_id";
        $stmt  = $conexao->prepare($query);
        $stmt->bindParam(':session_id', $session_id, PDO::PARAM_INT);
    }

    $stmt->execute();
    $rows = $stmt->rowCount();

    if ($rows > 0) {
        echo json_encode(['success' => true, 'message' => "Sessão ID {$session_id} derrubada."]);
    } else {
        echo json_encode(['success' => false, 'message' => "Sessão ID {$session_id} não encontrada ou sem permissão."]);
    }
} catch (PDOException $e) {
    error_log("ERRO AO DERRUBAR SESSÃO: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro de banco de dados.']);
}
