<?php
// chatbot_integrado_funcoes.php
// Versão final com todas as funções para Chatbot, Área do Cliente e Pagamentos.

require_once(__DIR__ . '/api/controles/db.php');

// ======================================================
// FUNÇÕES PARA O CHATBOT
// ======================================================

function getChatbotUrl($admin_id) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("SELECT chatbot_token FROM admin WHERE id = ?");
    $stmt->execute([$admin_id]);
    $token = $stmt->fetchColumn();

    if (!$token) {
        $token = bin2hex(random_bytes(32));
        $stmt_update = $conn->prepare("UPDATE admin SET chatbot_token = ? WHERE id = ?");
        $stmt_update->execute([$token, $admin_id]);
    }
    
    $base_url = "https://" . $_SERVER['HTTP_HOST'] . "/api/chatbot_office_api.php";
    return $base_url . "?key=" . $token;
}

function getUserByChatbotToken($token) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("SELECT * FROM admin WHERE chatbot_token = ? LIMIT 1");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addChatBotRule($admin_id, $rule_type, $rule_action, $response, $messages) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("INSERT INTO chatbot (admin_id, rule_type, rule_action, response, status) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$admin_id, $rule_type, $rule_action, $response]);
    $chatbot_id = $conn->lastInsertId();

    if ($chatbot_id) {
        foreach ($messages as $message) {
            if (!empty(trim($message))) {
                $stmt_msg = $conn->prepare("INSERT INTO chatbot_messages (chatbot_id, admin_id, message) VALUES (?, ?, ?)");
                $stmt_msg->execute([$chatbot_id, $admin_id, trim($message)]);
            }
        }
    }
    return $chatbot_id ? true : false;
}

function updateChatBotRule($admin_id, $chatbot_id, $rule_type, $rule_action, $response, $messages) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("UPDATE chatbot SET rule_type = ?, rule_action = ?, response = ? WHERE id = ? AND admin_id = ?");
    $stmt->execute([$rule_type, $rule_action, $response, $chatbot_id, $admin_id]);
    
    $stmt_del = $conn->prepare("DELETE FROM chatbot_messages WHERE chatbot_id = ? AND admin_id = ?");
    $stmt_del->execute([$chatbot_id, $admin_id]);

    foreach ($messages as $message) {
        if (!empty(trim($message))) {
            $stmt_msg = $conn->prepare("INSERT INTO chatbot_messages (chatbot_id, admin_id, message) VALUES (?, ?, ?)");
            $stmt_msg->execute([$chatbot_id, $admin_id, trim($message)]);
        }
    }
    return true;
}

function getChatbotRuleById($chatbot_id, $admin_id) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("SELECT * FROM chatbot WHERE id = ? AND admin_id = ?");
    $stmt->execute([$chatbot_id, $admin_id]);
    $rule = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($rule) {
        $stmt_msg = $conn->prepare("SELECT message FROM chatbot_messages WHERE chatbot_id = ?");
        $stmt_msg->execute([$chatbot_id]);
        $messages = $stmt_msg->fetchAll(PDO::FETCH_COLUMN);
        $rule['messages'] = $messages;
    }
    return $rule;
}

function getAllChatbotRulesByAdmin($admin_id) {
     $conn = conectar_bd();
     $stmt = $conn->prepare("SELECT * FROM chatbot WHERE admin_id = ? ORDER BY id DESC");
     $stmt->execute([$admin_id]);
     $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

     foreach ($rules as $key => $rule) {
        $stmt_msg = $conn->prepare("SELECT message FROM chatbot_messages WHERE chatbot_id = ?");
        $stmt_msg->execute([$rule['id']]);
        $messages = $stmt_msg->fetchAll(PDO::FETCH_COLUMN);
        $rules[$key]['messages'] = $messages;
     }
     return $rules;
}

function incrementChatbotRuleRuns($chatbot_id) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("UPDATE chatbot SET runs = runs + 1 WHERE id = ?");
    $stmt->execute([$chatbot_id]);
}

function deleteChatBotRule($admin_id, $rule_id) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("DELETE FROM chatbot WHERE id = ? AND admin_id = ?");
    $stmt->execute([$rule_id, $admin_id]);
    return $stmt->rowCount() > 0;
}

// ======================================================
// FUNÇÕES PARA A ÁREA DO CLIENTE
// ======================================================

function loginCliente($usuario, $senha) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE usuario = ? AND senha = ? AND is_trial = 0 LIMIT 1");
    $stmt->execute([$usuario, $senha]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        $_SESSION['cliente_id'] = $cliente['id'];
        return true;
    }
    return false;
}

function isClienteLogged() {
    if (!isset($_SESSION['cliente_id'])) {
        if (isset($_GET['logout'])) {
            session_unset();
            session_destroy();
        }
        header("Location: login_cliente.php");
        exit;
    }
}

function getClienteLogado() {
    if (isset($_SESSION['cliente_id'])) {
        $conn = conectar_bd();
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$_SESSION['cliente_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return false;
}

// ======================================================
// FUNÇÕES PARA PAGAMENTO E RENOVAÇÃO AUTOMÁTICA
// ======================================================

function getPlanoDoCliente($cliente_id) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("SELECT p.* FROM planos p JOIN clientes c ON c.plano = p.id WHERE c.id = ?");
    $stmt->execute([$cliente_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function renovarCliente($cliente_id, $meses = 1) {
    $conn = conectar_bd();
    $stmt_cliente = $conn->prepare("SELECT Vencimento FROM clientes WHERE id = ?");
    $stmt_cliente->execute([$cliente_id]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        $vencimento_atual = $cliente['Vencimento'];
        $base_data = (strtotime($vencimento_atual) > time()) ? $vencimento_atual : date("Y-m-d H:i:s");
        $nova_data = date("Y-m-d H:i:s", strtotime("{$base_data} +{$meses} month"));

        $stmt_update = $conn->prepare("UPDATE clientes SET Vencimento = ?, is_trial = 0, Ultimo_pagamento = NOW() WHERE id = ?");
        return $stmt_update->execute([$nova_data, $cliente_id]);
    }
    return false;
}

function enviarNotificacaoWhatsApp($numero, $mensagem) {
    // LÓGICA DE ENVIO DE WHATSAPP AQUI
    // Por enquanto, vamos apenas registar no log de erros para simular o envio.
    error_log("Simulando envio de WhatsApp para {$numero}: {$mensagem}");
    return true;
}

// ======================================================
// FUNÇÕES PARA CONFIGURAÇÃO DE PAGAMENTO DO REVENDEDOR
// ======================================================

/**
 * Guarda as credenciais de pagamento de um administrador/revendedor.
 * @param int $admin_id O ID do admin.
 * @param string $token O Access Token do Mercado Pago.
 * @param string $secret A Assinatura Secreta do Webhook.
 * @return bool True em caso de sucesso.
 */
function saveAdminPaymentConfig($admin_id, $token, $secret) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("UPDATE admin SET mp_access_token = ?, mp_webhook_secret = ? WHERE id = ?");
    return $stmt->execute([$token, $secret, $admin_id]);
}

/**
 * Obtém as credenciais de pagamento de um administrador/revendedor.
 * @param int $admin_id O ID do admin.
 * @return array|false As credenciais ou false.
 */
function getAdminPaymentConfig($admin_id) {
    $conn = conectar_bd();
    $stmt = $conn->prepare("SELECT mp_access_token, mp_webhook_secret FROM admin WHERE id = ?");
    $stmt->execute([$admin_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
