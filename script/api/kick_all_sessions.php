<?php
// Endpoint para derrubar sessões ativas.
// Admin derruba tudo; revendedor derruba apenas sessões dos seus clientes.
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
header('Content-Type: application/json; charset=utf-8');

$nivel = (int)($_SESSION['nivel_admin'] ?? -1);
if ($nivel !== 1 && $nivel !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

try {
    $conexao = conectar_bd();

    if ($nivel === 0) {
        $admin_id = (int)($_SESSION['admin_id'] ?? 0);

        // Coleta IPs antes de deletar para banir temporariamente
        $stmt_ips = $conexao->prepare(
            "SELECT DISTINCT con.ip FROM conexoes con
             INNER JOIN clientes cl ON cl.usuario = con.usuario AND cl.admin_id = :admin_id"
        );
        $stmt_ips->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $stmt_ips->execute();
        $ips = $stmt_ips->fetchAll(PDO::FETCH_COLUMN);

        $query = "DELETE con FROM conexoes con
                  INNER JOIN clientes cl ON cl.usuario = con.usuario AND cl.admin_id = :admin_id";
        $stmt = $conexao->prepare($query);
        $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    } else {
        // Coleta IPs antes de deletar
        $stmt_ips = $conexao->prepare("SELECT DISTINCT ip FROM conexoes");
        $stmt_ips->execute();
        $ips = $stmt_ips->fetchAll(PDO::FETCH_COLUMN);

        $query = "DELETE FROM conexoes";
        $stmt  = $conexao->prepare($query);
    }

    $stmt->execute();
    $rows = $stmt->rowCount();

    // Bane todos os IPs por 2 minutos para impedir reconexão imediata
    if (!empty($ips)) {
        $stmt_ban = $conexao->prepare(
            "INSERT INTO banned_ips (ip_address, reason, ban_expires)
             VALUES (:ip, 'Derrubada em massa', DATE_ADD(NOW(), INTERVAL 2 MINUTE))
             ON DUPLICATE KEY UPDATE
                 reason     = 'Derrubada em massa',
                 ban_expires = DATE_ADD(NOW(), INTERVAL 2 MINUTE)"
        );
        foreach ($ips as $ip) {
            $stmt_ban->bindParam(':ip', $ip);
            $stmt_ban->execute();
        }
    }

    $message = ($rows > 0)
        ? "Todas as {$rows} sessões foram derrubadas com sucesso."
        : "Nenhuma sessão ativa encontrada.";

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor (Database Error).']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}
