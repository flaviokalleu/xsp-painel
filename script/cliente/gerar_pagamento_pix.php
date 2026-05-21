<?php
session_start();
if (!isset($_SESSION['client_loggedin'])) { header('Location: index.php'); exit(); }

require_once('../api/controles/db.php');
require_once('../vendor/autoload.php');

$conexao = conectar_bd();
$client_id = $_SESSION['client_id'];
$plano_id = $_POST['plano_id'];

// 1. Busca dados do cliente (incluindo email) e do revendedor
$stmt_cliente = $conexao->prepare("SELECT admin_id, email, name FROM clientes WHERE id = ?");
$stmt_cliente->execute([$client_id]);
$cliente_data = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
$revendedor_id = $cliente_data['admin_id'];

// 2. Busca o Access Token do revendedor
$stmt_revendedor = $conexao->prepare("SELECT mp_access_token FROM revendedor_configuracoes WHERE revendedor_id = ?");
$stmt_revendedor->execute([$revendedor_id]);
$access_token = $stmt_revendedor->fetchColumn();

if (!$access_token) {
    $_SESSION['payment_error'] = 'Pagamento Pix não disponível. Contate o suporte.';
    header('Location: dashboard.php');
    exit();
}

// 3. Busca os detalhes do plano
$stmt_plano = $conexao->prepare("SELECT nome, valor FROM planos WHERE id = ?");
$stmt_plano->execute([$plano_id]);
$plano = $stmt_plano->fetch(PDO::FETCH_ASSOC);

if (!$plano) {
    $_SESSION['payment_error'] = 'Plano não encontrado. Contate o suporte.';
    header('Location: dashboard.php');
    exit();
}

try {
    MercadoPago\SDK::setAccessToken($access_token);

    $payment = new MercadoPago\Payment();
    $payment->transaction_amount = (float) $plano['valor'];
    $payment->description = "Renovação Plano: " . $plano['nome'];
    $payment->payment_method_id = "pix";
    $payment->external_reference = $client_id;

    // O pagador (payer) é obrigatório para Pix
    $payment->payer = array(
        "email" => $cliente_data['email'] ?: "cliente_{$client_id}@email.com", // Usa o email do cliente ou um placeholder
        "first_name" => $cliente_data['name']
    );

    $payment->save();

    if ($payment->id && $payment->point_of_interaction) {
        // Salva os dados do Pix na sessão para exibir na próxima página
        $_SESSION['pix_qr_code_base64'] = $payment->point_of_interaction->transaction_data->qr_code_base64;
        $_SESSION['pix_qr_code'] = $payment->point_of_interaction->transaction_data->qr_code;
        header("Location: exibir_pix.php");
        exit();
    } else {
        throw new Exception("Não foi possível obter os dados do Pix.");
    }

} catch (Exception $e) {
    error_log("Erro Pix: " . $e->getMessage());
    $_SESSION['payment_error'] = 'Ocorreu um erro ao gerar o Pix. Tente novamente.';
    header('Location: dashboard.php');
    exit();
}