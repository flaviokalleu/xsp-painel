<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
require_once 'vendor/autoload.php';

$pdo = conectar_bd();
if (!$pdo) { http_response_code(500); exit('Erro BD.'); }

// 1. IDENTIFICAR O REVENDEDOR E BUSCAR SEU ACCESS TOKEN
$token_url = $_GET['token'] ?? null;
if (!$token_url) { http_response_code(400); exit('Token não fornecido.'); }

$stmt_config = $pdo->prepare("SELECT revendedor_id, mp_access_token FROM revendedor_configuracoes WHERE webhook_token = ?");
$stmt_config->execute([$token_url]);
$revendedor_config = $stmt_config->fetch(PDO::FETCH_ASSOC);

if (!$revendedor_config || empty($revendedor_config['mp_access_token'])) {
    http_response_code(403); 
    exit('Access Token não encontrado para este token.');
}

$revendedor_id = $revendedor_config['revendedor_id'];
$accessToken = $revendedor_config['mp_access_token'];

// 2. CONFIGURAR O SDK COM O ACCESS TOKEN CORRETO (MÉTODO ANTIGO E COMPATÍVEL)
MercadoPago\SDK::setAccessToken($accessToken);

// 3. PROCESSAR O PAGAMENTO
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if(!isset($data["type"]) || $data["type"] != "payment" || !isset($data['data']['id'])) {
    http_response_code(200);
    exit();
}

try {
    $payment = MercadoPago\Payment::find_by_id($data['data']['id']);

    if ($payment && $payment->status == 'approved') {
        $user_id_cliente = $payment->external_reference;
        if (empty($user_id_cliente)) { exit(); }
        
        // Busca o cliente e verifica se ele pertence ao revendedor correto
        $stmt = $pdo->prepare("SELECT id, usuario, Vencimento, admin_id FROM clientes WHERE id = ?");
        $stmt->execute([$user_id_cliente]);
        $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario_db && $usuario_db['admin_id'] == $revendedor_id) {
            // Se o cliente pertence ao revendedor, pode renovar!
            $plano_duracao_dias = 30;
            $data_expiracao_atual_timestamp = strtotime($usuario_db['Vencimento']);
            $base_para_calculo = ($data_expiracao_atual_timestamp > time()) ? $data_expiracao_atual_timestamp : time();
            $nova_data_expiracao = date('Y-m-d H:i:s', $base_para_calculo + ($plano_duracao_dias * 86400));

            // ATUALIZA O CLIENTE DIRETAMENTE NO BANCO DE DADOS
            $stmt_update = $pdo->prepare("UPDATE clientes SET Vencimento = ? WHERE id = ?");
            $stmt_update->execute([$nova_data_expiracao, $usuario_db['id']]);
        }
    }
} catch (Exception $e) {
    // Grava o erro em um log do servidor para você poder investigar depois
    error_log("Erro no Webhook MP (Versão Compatível): " . $e->getMessage());
}

http_response_code(200);
?>