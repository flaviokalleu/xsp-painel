<?php
session_start();
require_once 'vendor/autoload.php';
require_once '../api/controles/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = conectar_bd();
$preco_do_plano = 0;
$nome_do_plano = "Plano Padrão";
$email_cliente = $_SESSION['username'] . '@seusite.com'; 

if ($pdo) {
    $sql = "SELECT c.usuario, p.nome, p.valor 
            FROM clientes c 
            JOIN planos p ON c.plano = p.id 
            WHERE c.id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        $preco_do_plano = (float)$resultado['valor'];
        $nome_do_plano = $resultado['nome'];
    }
}

if ($preco_do_plano <= 0) {
    die("Erro: Plano inválido ou sem valor definido. Entre em contato com o suporte.");
}

// ====================================================================
// PREENCHA AQUI com seu Access Token de PRODUÇÃO do Mercado Pago
$accessToken = "APP_USR-2335068913257714-010413-21c2fa03091d6818b8744c97a00450bd-320369705";
// ====================================================================
MercadoPago\SDK::setAccessToken($accessToken);

try {
    $payment = new MercadoPago\Payment();
    $payment->transaction_amount = $preco_do_plano;
    $payment->description = "Renovacao Plano: " . htmlspecialchars($nome_do_plano);
    $payment->payment_method_id = "pix";
    $payment->notification_url = "https://topiptv.tvsbr.top/clientes/webhook.php";
    $payment->external_reference = $_SESSION['user_id']; 

    $payment->payer = new MercadoPago\Payer();
    $payment->payer->email = $email_cliente;

    $payment->save();

    if (isset($payment->point_of_interaction->transaction_data)) {
        $qr_code_base64 = $payment->point_of_interaction->transaction_data->qr_code_base64;
        $qr_code = $payment->point_of_interaction->transaction_data->qr_code;
    } else {
        die("Não foi possível gerar o código Pix. Verifique suas credenciais do Mercado Pago.");
    }
} catch (Exception $e) {
    die("Ocorreu um erro com a API do Mercado Pago: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pagar com Pix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f0f2f5; font-family: system-ui, sans-serif; }
        .pix-card { max-width: 450px; width: 100%; }
        .qr-code-img { max-width: 280px; width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 10px; }
        .copy-code { word-break: break-all; }
    </style>
</head>
<body>
    <div class="card pix-card text-center shadow">
        <div class="card-header bg-primary text-white"><h4>Pague com Pix para Renovar</h4></div>
        <div class="card-body p-4">
            <p class="fs-5">Valor: <strong class="text-primary">R$ <?php echo number_format($preco_do_plano, 2, ',', '.'); ?></strong></p>
            <p>Escaneie o QR Code abaixo com o seu aplicativo do banco:</p>
            <img src="data:image/jpeg;base64, <?php echo $qr_code_base64; ?>" alt="QR Code Pix" class="qr-code-img mb-3">
            <p class="mt-3">Ou use o Pix Copia e Cola:</p>
            <div class="input-group">
                <input type="text" id="pixCode" class="form-control copy-code" value="<?php echo $qr_code; ?>" readonly>
                <button class="btn btn-primary" onclick="copyPixCode()" id="copyButton">Copiar</button>
            </div>
            <div class="alert alert-info mt-3"><small>Após o pagamento, sua assinatura será renovada automaticamente.</small></div>
        </div>
        <div class="card-footer text-muted"><a href="painel.php">Voltar para o Painel</a></div>
    </div>
    <script>
        function copyPixCode() {
            const pixCodeInput = document.getElementById('pixCode');
            const copyButton = document.getElementById('copyButton');
            pixCodeInput.select();
            pixCodeInput.setSelectionRange(0, 99999);
            try {
                document.execCommand('copy');
                copyButton.innerText = 'Copiado!';
                setTimeout(() => { copyButton.innerText = 'Copiar'; }, 2000);
            } catch (err) { alert('Não foi possível copiar o código.'); }
        }
    </script>
</body>
</html>