<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("chatbot_integrado_funcoes.php");
require_once("vendor/autoload.php"); // Carrega o SDK do Mercado Pago

isClienteLogged();
$cliente = getClienteLogado();
$plano = getPlanoDoCliente($cliente['id']);

if (!$plano || $plano['valor'] <= 0) {
    die("Erro: Plano inválido ou sem valor definido. Contacte o suporte.");
}

// ======================================================
// 1. COLOQUE A SUA CHAVE DO MERCADO PAGO AQUI
// ======================================================
$accessToken = "SEU_ACCESS_TOKEN_DE_PRODUCAO_AQUI";
// ======================================================

MercadoPago\SDK::setAccessToken($accessToken);

$preference = new MercadoPago\Preference();

// Cria um item para o pagamento
$item = new MercadoPago\Item();
$item->title = "Renovação Plano: " . $plano['nome'];
$item->quantity = 1;
$item->unit_price = (float)$plano['valor'];
$item->currency_id = "BRL";

$preference->items = array($item);

// Define URLs de retorno e notificação
$preference->back_urls = array(
    "success" => "https://" . $_SERVER['HTTP_HOST'] . "/painel_cliente.php?status=sucesso",
    "failure" => "https://" . $_SERVER['HTTP_HOST'] . "/painel_cliente.php?status=falha",
    "pending" => "https://" . $_SERVER['HTTP_HOST'] . "/painel_cliente.php?status=pendente"
);
$preference->auto_return = "approved";

// URL que o Mercado Pago irá chamar para nos avisar do pagamento
$preference->notification_url = "https://" . $_SERVER['HTTP_HOST'] . "/notificacao_mp.php";

// Identificador único para sabermos quem pagou
$preference->external_reference = $cliente['id'];

$preference->save();

// Redireciona o cliente para a página de pagamento
header("Location: " . $preference->init_point);
exit();
?>
