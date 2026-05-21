<?php
session_start();
if (!isset($_SESSION['client_loggedin'])) { 
    header('Location: index.php'); 
    exit(); 
}

require_once('../api/controles/db.php');
require_once('../vendor/autoload.php');

$conexao = conectar_bd();
$client_id = $_SESSION['client_id'];
$plano_id = $_POST['plano_id'];

// 1. Busca o Access Token do revendedor
$stmt_cliente = $conexao->prepare("SELECT admin_id FROM clientes WHERE id = ?");
$stmt_cliente->execute([$client_id]);
$revendedor_id = $stmt_cliente->fetchColumn();

$stmt_revendedor = $conexao->prepare("SELECT mp_access_token FROM revendedor_configuracoes WHERE revendedor_id = ?");
$stmt_revendedor->execute([$revendedor_id]);
$access_token = $stmt_revendedor->fetchColumn();

if (!$access_token) {
    $_SESSION['payment_error'] = 'Este revendedor não configurou um método de pagamento. Entre em contato com o suporte.';
    header('Location: dashboard.php');
    exit();
}

// 2. Busca os detalhes do plano
$stmt_plano = $conexao->prepare("SELECT nome, valor FROM planos WHERE id = ?");
$stmt_plano->execute([$plano_id]);
$plano = $stmt_plano->fetch(PDO::FETCH_ASSOC);

if (!$plano) {
    $_SESSION['payment_error'] = 'Plano não encontrado. Entre em contato com o suporte.';
    header('Location: dashboard.php');
    exit();
}

try {
    MercadoPago\SDK::setAccessToken($access_token);

    $preference = new MercadoPago\Preference();
    $item = new MercadoPago\Item();
    
    $item->title = "Renovação Plano: " . $plano['nome'];
    $item->quantity = 1;
    $item->unit_price = (float) $plano['valor'];
    $item->currency_id = "BRL";

    $preference->items = array($item);
    $preference->external_reference = $client_id;
    
    $preference->back_urls = array(
        "success" => "https://" . $_SERVER['HTTP_HOST'] . "/cliente/dashboard.php",
        "failure" => "https://" . $_SERVER['HTTP_HOST'] . "/cliente/dashboard.php",
        "pending" => "https://" . $_SERVER['HTTP_HOST'] . "/cliente/dashboard.php"
    );
    $preference->auto_return = "approved";

    // --- CÓDIGO ATUALIZADO E MAIS EFICAZ ---
    // Aqui dizemos explicitamente quais métodos de pagamento queremos.
    // O Mercado Pago então mostrará o Pix como opção principal se estiver disponível.
    $preference->payment_methods = array(
      "excluded_payment_methods" => array(),
      "excluded_payment_types" => array(
        array("id" => "ticket") // Continuamos excluindo o boleto
      ),
      "installments" => 1
    );
    // --- FIM DO BLOCO ATUALIZADO ---

    $preference->save();

    if (isset($preference->init_point)) {
        header("Location: " . $preference->init_point);
        exit();
    } else {
        $_SESSION['payment_error'] = 'Não foi possível gerar o link de pagamento. Tente novamente.';
        header('Location: dashboard.php');
        exit();
    }

} catch (Exception $e) {
    error_log("Erro Mercado Pago: " . $e->getMessage());
    $_SESSION['payment_error'] = 'Ocorreu um erro com o sistema de pagamento. Tente mais tarde.';
    header('Location: dashboard.php');
    exit();
}