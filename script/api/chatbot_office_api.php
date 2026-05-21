<?php
// /api/chatbot_office_api.php
// VERSÃO FINAL COM MENSAGEM PERSONALIZADA

session_start(); 
header('Content-Type: application/json');

require_once(__DIR__ . '/../chatbot_integrado_funcoes.php');
require_once(__DIR__ . '/../api/controles/testes.php'); 

$plano_padrao_para_teste = 1; 
$duracao_padrao_horas = 3;    
$dominio_servidor = "http://" . $_SERVER['HTTP_HOST'];

$data = json_decode(file_get_contents('php://input'), true);

function responder($mensagem = "") {
    die(json_encode(["data" => [["message" => $mensagem]]]));
}

$key = $_GET['key'] ?? '';
if (empty($key)) { responder("Identificador inválido."); }

$admin = getUserByChatbotToken($key);
if (!$admin) { responder("Identificador não encontrado."); }

$_SESSION['admin_id'] = $admin['id'];
$_SESSION['token'] = $admin['token'];

$userMessage = $data['senderMessage'] ?? '';
if (empty(trim($userMessage))) { die(); }

$chatbotRules = getAllChatbotRulesByAdmin($admin['id']);

foreach ($chatbotRules as $rule) {
    if ($rule["status"] != 1) continue;

    foreach ($rule["messages"] as $message) {
        $match = false;
        if (($rule["rule_type"] === "equals" && strtolower($message) === strtolower($userMessage)) || ($rule["rule_type"] === "contains" && stripos(strtolower($userMessage), strtolower($message)) !== false)) {
            $match = true;
        }
        
        if ($match) {
            $response_message = "";
            if ($rule["rule_action"] === 'test_iptv') {
                $usuario_teste = strtolower(substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8));
                $senha_teste = strtolower(substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 6));
                $resultado_criacao = confirme_adicionar_testes('Teste via Chatbot', $usuario_teste, $senha_teste, 0, $plano_padrao_para_teste, 'N/A', 'N/A', 'PIX', 'Cliente Chatbot', '', null, null, null, $duracao_padrao_horas);
                
                if (isset($resultado_criacao['icon']) && $resultado_criacao['icon'] === 'success') {
                    $vencimento = date('d/m/Y H:i', strtotime("+$duracao_padrao_horas hours"));
                    
                    $link_m3u_completo = "{$dominio_servidor}/get.php?username={$usuario_teste}&password={$senha_teste}&type=m3u_plus&output=ts";
                    $link_ssiptv_encurtado = "{$dominio_servidor}/ss-ts/{$usuario_teste}/{$senha_teste}";
                    $url_xciptv = $dominio_servidor;

                    $response_message = "Seu teste foi gerado com sucesso!\n\n";
                    $response_message .= "DADOS DE ACESSO:\n";
                    $response_message .= "Usuario: " . $usuario_teste . "\n";
                    $response_message .= "Senha: " . $senha_teste . "\n";
                    $response_message .= "Vencimento: " . $vencimento . "\n\n";
                    
                    $response_message .= "URL para App XCIPTV:\n";
                    $response_message .= $url_xciptv . "\n\n";
                    
                    $response_message .= "URL para App Smarts Player Pro:\n";
                    $response_message .= "http:\\\play.tvsbr.top:80\n\n";
                    
                    $response_message .= "LISTAS:\n";
                    $response_message .= "Lista M3U (Completa):\n";
                    $response_message .= $link_m3u_completo . "\n\n";
                    $response_message .= "Lista SSIPTV (Encurtada):\n";
                    $response_message .= $link_ssiptv_encurtado;

                    // ===============================================
                    // SUA MENSAGEM ADICIONADA AQUI
                    // ===============================================
                    $response_message .= "\n\nObrigado pela preferencia, TOP IPTV";
                    // ===============================================

                } else {
                    $response_message = "Desculpe, nao foi possivel gerar seu teste. Motivo: " . ($resultado_criacao['msg'] ?? 'Erro desconhecido no painel.');
                }
            } else { 
                $response_message = $rule["response"];
            }
            incrementChatbotRuleRuns($rule["id"]);
            responder($response_message);
        }
    }
}
?>