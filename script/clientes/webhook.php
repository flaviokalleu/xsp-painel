<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
require_once 'vendor/autoload.php';

// ====================================================================
// PREENCHA AQUI suas credenciais
// ====================================================================
$api_file_url = 'https://coloca.seu.dominio/panel_api.php';
$xtream_admin_user = 'usuario';
$xtream_admin_pass = 'senha'; // SUA NOVA SENHA DO PAINEL ADMIN
$plano_duracao_dias = 30;
$accessToken = "sua credenciais mercado pago";
$mercado_pago_signing_secret = 'sua credenciais mercado pago';
// ====================================================================
MercadoPago\SDK::setAccessToken($accessToken);

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
        
        $pdo = conectar_bd();
        if (!$pdo) { exit('Erro de BD.'); }
        
        $stmt = $pdo->prepare("SELECT id, usuario, Vencimento FROM clientes WHERE id = ?");
        $stmt->execute([$user_id_cliente]);
        $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario_db) {
            $data_expiracao_atual_timestamp = strtotime($usuario_db['Vencimento']);
            $base_para_calculo = ($data_expiracao_atual_timestamp > time()) ? $data_expiracao_atual_timestamp : time();
            $nova_data_expiracao_timestamp = $base_para_calculo + ($plano_duracao_dias * 24 * 60 * 60);
            
            $api_params = [
                'username' => $xtream_admin_user, 
                'password' => $xtream_admin_pass, 
                'action' => 'edit_user',
                'user_id' => $usuario_db['id'],
                'exp_date' => $nova_data_expiracao_timestamp
            ];
            $api_url = $api_file_url . '?' . http_build_query($api_params);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }
    }
} catch (Exception $e) {
    // Silenciosamente ignora o erro para não alertar o Mercado Pago
}

http_response_code(200);
?>