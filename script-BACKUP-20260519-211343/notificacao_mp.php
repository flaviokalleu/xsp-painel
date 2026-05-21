<?php
// /notificacao_mp.php
// VERSÃO DE DEPURAÇÃO PARA ENCONTRAR O ERRO DE RENOVAÇÃO

// Força o registo de todos os erros
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once("vendor/autoload.php");
require_once("chatbot_integrado_funcoes.php");

// --- INÍCIO DO LOG ---
error_log("=================================================");
error_log("Webhook do Mercado Pago recebido em: " . date('Y-m-d H:i:s'));

// ======================================================
// 1. COLOQUE AS SUAS CHAVES DO MERCADO PAGO AQUI
// ======================================================
$accessToken = "APP_USR-2335068913257714-010413-21c2fa03091d6818b8744c97a00450bd-320369705";
$secretKey = "5fddba984e79f6a8c094e529c36b3cfdeda22834c701322707f0b1b21e62952f";
// ======================================================

MercadoPago\SDK::setAccessToken($accessToken);

$requestBody = file_get_contents('php://input');
$requestHeaders = getallheaders();
error_log("Corpo da Requisição: " . $requestBody);
error_log("Cabeçalhos da Requisição: " . json_encode($requestHeaders));

$signatureHeader = $requestHeaders['X-Signature'] ?? $requestHeaders['x-signature'] ?? null;

if ($signatureHeader) {
    error_log("Assinatura encontrada no cabeçalho: " . $signatureHeader);
    parse_str(str_replace(',', '&', $signatureHeader), $signatureParts);
    $ts = $signatureParts['ts'] ?? null;
    $hash = $signatureParts['v1'] ?? null;

    $data = json_decode($requestBody, true);
    $payment_id = $data['data']['id'] ?? null;
    $manifest = "id:{$payment_id};request-id:{$ts};ts:{$ts};";
    $localSignature = hash_hmac('sha256', $manifest, $secretKey);

    if (hash_equals($localSignature, $hash)) {
        error_log("ASSINATURA VÁLIDA. A processar o pagamento.");
        
        if (isset($data['type']) && $data['type'] === 'payment') {
            try {
                $payment = MercadoPago\Payment::find_by_id($payment_id);
                error_log("Detalhes do pagamento obtidos: Status -> " . $payment->status);

                if ($payment && $payment->status == 'approved') {
                    $cliente_id = $payment->external_reference;
                    error_log("Pagamento aprovado para o cliente ID: " . $cliente_id);

                    if ($cliente_id) {
                        $conn = conectar_bd();
                        $stmt_cliente = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
                        $stmt_cliente->execute([$cliente_id]);
                        $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

                        if ($cliente) {
                            error_log("Cliente encontrado no banco de dados: " . $cliente['usuario']);
                            $plano = getPlanoDoCliente($cliente_id);
                            $meses_a_renovar = $plano['meses'] ?? 1;
                            error_log("A renovar por {$meses_a_renovar} mes(es).");

                            if (renovarCliente($cliente_id, $meses_a_renovar)) {
                                error_log("SUCESSO: Cliente renovado no banco de dados.");
                                $nova_data_vencimento = date('d/m/Y', strtotime("+{$meses_a_renovar} month"));
                                $mensagem_whatsapp = "✅ Pagamento confirmado!\n\nOlá, {$cliente['name']}!\n\nA sua assinatura foi renovada com sucesso. O seu novo vencimento é {$nova_data_vencimento}.\n\nObrigado!";

                                if (!empty($cliente['Whatsapp'])) {
                                    enviarNotificacaoWhatsApp($cliente['Whatsapp'], $mensagem_whatsapp);
                                    error_log("Tentativa de envio de notificação para o WhatsApp: " . $cliente['Whatsapp']);
                                }
                                http_response_code(200);
                            } else {
                                error_log("FALHA CRÍTICA: A função renovarCliente() falhou.");
                            }
                        } else {
                             error_log("FALHA CRÍTICA: Cliente com ID {$cliente_id} não foi encontrado no banco de dados.");
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("ERRO FATAL no processamento: " . $e->getMessage());
                http_response_code(500);
            }
        }

    } else {
        error_log("FALHA DE SEGURANÇA: Assinatura do Webhook inválida.");
        http_response_code(400);
    }
} else {
    error_log("FALHA DE SEGURANÇA: Nenhum cabeçalho de assinatura (X-Signature) encontrado.");
    http_response_code(400);
}
error_log("=================================================\n");
?>
