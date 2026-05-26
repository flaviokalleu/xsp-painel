<?php
// api/ip_manager.php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once('./controles/db.php');

// Apenas administradores podem gerir IPs
if (!isset($_SESSION['nivel_admin']) || $_SESSION['nivel_admin'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado.']);
    exit;
}

$action = $_GET['action'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);
$conn = conectar_bd();

try {
    switch ($action) {
        case 'get_lists':
            $stmt_banned = $conn->prepare("SELECT ip_address, reason, ban_timestamp FROM banned_ips WHERE ban_expires > NOW() ORDER BY ban_timestamp DESC");
            $stmt_banned->execute();
            $banned_list = $stmt_banned->fetchAll(PDO::FETCH_ASSOC);

            $stmt_allowed = $conn->prepare("SELECT ip_address, notes FROM allowed_ips ORDER BY id DESC");
            $stmt_allowed->execute();
            $allowed_list = $stmt_allowed->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['banned' => $banned_list, 'allowed' => $allowed_list]);
            break;
            
        // INÍCIO DA MODIFICAÇÃO: Nova ação para bloquear IP manualmente
        case 'block_ip':
            $ip = $data['ip'] ?? null;
            $reason = $data['reason'] ?? 'Bloqueio Manual';
            // Define a expiração para 10 anos no futuro (simulando bloqueio permanente)
            $ban_expires = date('Y-m-d H:i:s', strtotime('+10 years')); 
            
            if ($ip) {
                // Insere ou atualiza o IP bloqueado. ON DUPLICATE KEY UPDATE garante que se o IP já existir, ele será atualizado.
                $stmt = $conn->prepare("INSERT INTO banned_ips (ip_address, reason, ban_timestamp, ban_expires) 
                                        VALUES (?, ?, NOW(), ?) 
                                        ON DUPLICATE KEY UPDATE reason = VALUES(reason), ban_timestamp = NOW(), ban_expires = VALUES(ban_expires)");
                $stmt->execute([$ip, $reason, $ban_expires]);
                echo json_encode(['success' => true, 'message' => "IP {$ip} bloqueado manualmente."]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'IP não fornecido para bloqueio.']);
            }
            break;
        // FIM DA MODIFICAÇÃO

        case 'unblock_ips':
            $ips = $data['ips'] ?? [];
            if (!empty($ips)) {
                $placeholders = implode(',', array_fill(0, count($ips), '?'));
                $stmt = $conn->prepare("DELETE FROM banned_ips WHERE ip_address IN ($placeholders)");
                $stmt->execute($ips);
                echo json_encode(['success' => true, 'message' => count($ips) . ' IP(s) desbloqueado(s).']);
            }
            break;

        case 'allow_ip':
            $ip = $data['ip'] ?? null;
            $notes = $data['notes'] ?? '';
            if ($ip) {
                $stmt = $conn->prepare("INSERT INTO allowed_ips (ip_address, notes) VALUES (?, ?) ON DUPLICATE KEY UPDATE notes = VALUES(notes)");
                $stmt->execute([$ip, $notes]);
                echo json_encode(['success' => true, 'message' => 'IP adicionado à whitelist.']);
            }
            break;

        case 'remove_allowed':
            $ip = $data['ip'] ?? null;
            if ($ip) {
                $stmt = $conn->prepare("DELETE FROM allowed_ips WHERE ip_address = ?");
                $stmt->execute([$ip]);
                echo json_encode(['success' => true, 'message' => 'IP removido da whitelist.']);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Ação inválida.']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na base de dados.', 'details' => $e->getMessage()]);
}
?>