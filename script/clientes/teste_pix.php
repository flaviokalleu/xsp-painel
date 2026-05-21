<?php
// Script de Teste Mínimo para Isolar o Problema do Pix no Mercado Pago

require_once 'vendor/autoload.php';

// ======================= CONFIGURAÇÕES =======================
// COLE AQUI O SEU ACCESS TOKEN DE PRODUÇÃO DO MERCADO PAGO
MercadoPago\SDK::setAccessToken("APP_USR-2335068913257714-010413-21c2fa03091d6818b8744c97a00450bd-320369705"); 
// =============================================================

echo "<h1>Teste de Geração de Pix</h1>";
echo "<p>Tentando criar uma preferência de pagamento de R$ 1,00...</p>";

try {
    // 1. CRIAR UMA PREFERÊNCIA DE PAGAMENTO SIMPLES E FIXA
    $preference = new MercadoPago\Preference();
    
    $item = new MercadoPago\Item();
    $item->title = "Produto de Teste";
    $item->quantity = 1;
    $item->unit_price = 1.00; // Preço fixo para o teste
    
    $preference->items = array($item);
    $preference->external_reference = "teste_pix_" . time(); // Referência simples
    
    // Tenta salvar a preferência
    $preference->save();

    // 2. SE TIVER SUCESSO, MOSTRA O LINK
    if (isset($preference->init_point) && !empty($preference->init_point)) {
        echo "<p style='color:green;'>Preferência criada com sucesso!</p>";
        echo "<p>Clique no link abaixo para ir para a tela de pagamento:</p>";
        echo "<a href='" . $preference->init_point . "' target='_blank' style='font-size:18px;'>" . $preference->init_point . "</a>";
    } else {
        echo "<p style='color:red;'>ERRO: A preferência foi criada, mas não retornou um link de pagamento (init_point).</p>";
        echo "<pre>";
        print_r($preference);
        echo "</pre>";
    }

} catch (Exception $e) {
    // 3. SE DER ERRO NA CRIAÇÃO, MOSTRA A MENSAGEM
    echo "<p style='color:red;'>ERRO CRÍTICO ao tentar criar a preferência de pagamento:</p>";
    echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
}
?>