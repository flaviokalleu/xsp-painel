<?php
// security_check.php (Versão 2.0 com Whitelist e Bans Permanentes)

function check_request_limit() {
    if (session_status() == PHP_SESSION_NONE) { session_start(); }
    
    $visitor_ip = $_SERVER['REMOTE_ADDR'];

    try {
        $conn = conectar_bd();

        // 1. O IP está na Whitelist? Se sim, permite o acesso imediatamente.
        $stmt_allowed = $conn->prepare("SELECT id FROM allowed_ips WHERE ip_address = ?");
        $stmt_allowed->execute([$visitor_ip]);
        if ($stmt_allowed->fetch()) {
            return; // Acesso permitido, termina a verificação.
        }

        // 2. O IP está na lista de banidos da base de dados?
        $stmt_banned = $conn->prepare("SELECT ban_expires FROM banned_ips WHERE ip_address = ?");
        $stmt_banned->execute([$visitor_ip]);
        $ban_info = $stmt_banned->fetch();

        if ($ban_info && strtotime($ban_info['ban_expires']) > time()) {
            http_response_code(429);
            echo json_encode(['error' => 'Acesso bloqueado por atividade suspeita.']);
            exit;
        }

        // --- Lógica de contagem de pedidos (como antes) ---
        $max_requests = 100;
        $time_window_seconds = 300;
        $ban_duration_hours = 1;

        $stmt_log = $conn->prepare("INSERT INTO request_logs (ip_address, user_agent) VALUES (?, ?)");
        $stmt_log->execute([$visitor_ip, $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);

        $stmt_count = $conn->prepare(
            "SELECT COUNT(id) FROM request_logs WHERE ip_address = ? AND request_timestamp >= NOW() - INTERVAL ? SECOND"
        );
        $stmt_count->execute([$visitor_ip, $time_window_seconds]);
        $request_count = $stmt_count->fetchColumn();

        if ($request_count > $max_requests) {
            // 3. Adiciona o IP à tabela de banidos
            $ban_expires = date('Y-m-d H:i:s', time() + ($ban_duration_hours * 3600));
            $stmt_ban = $conn->prepare("INSERT INTO banned_ips (ip_address, ban_expires) VALUES (?, ?) ON DUPLICATE KEY UPDATE ban_expires = VALUES(ban_expires)");
            $stmt_ban->execute([$visitor_ip, $ban_expires]);
            
            error_log("ATAQUE DETETADO: IP " . $visitor_ip . " bloqueado por " . $ban_duration_hours . " hora(s).");
            
            http_response_code(429);
            echo json_encode(['error' => 'Acesso bloqueado por atividade suspeita.']);
            exit;
        }

        if (rand(1, 100) <= 1) {
            $conn->exec("DELETE FROM request_logs WHERE request_timestamp < NOW() - INTERVAL 1 DAY");
        }

    } catch (PDOException $e) {
        error_log("Erro no security_check v2.0: " . $e->getMessage());
    }
}

check_request_limit();
?>