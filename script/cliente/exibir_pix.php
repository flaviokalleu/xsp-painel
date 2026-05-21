<?php
session_start();
if (!isset($_SESSION['client_loggedin'])) {
    header('Location: dashboard.php');
    exit();
}

// Vamos buscar a data de vencimento atual para passar para o JavaScript
require_once('../api/controles/db.php');
$conexao = conectar_bd();
$stmt = $conexao->prepare("SELECT Vencimento FROM clientes WHERE id = ?");
$stmt->execute([$_SESSION['client_id']]);
$vencimento_atual = $stmt->fetchColumn();

// Pega os dados do Pix da sessão
$pix_qr_code_base64 = $_SESSION['pix_qr_code_base64'] ?? null;
$pix_qr_code = $_SESSION['pix_qr_code'] ?? null;

// Se não houver dados do Pix, redireciona
if (!$pix_qr_code) {
    header('Location: dashboard.php');
    exit();
}

// Limpa as variáveis da sessão
unset($_SESSION['pix_qr_code_base64']);
unset($_SESSION['pix_qr_code']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pagar com Pix</title>
    <link rel="stylesheet" href="style.css">
    <style> .container { max-width: 500px; } </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-card">
            <div id="payment-screen">
                <h1 class="card-title"><i class="fas fa-qrcode me-2"></i> Pague com Pix</h1>
                <p style="text-align: center; margin-top: -20px; margin-bottom: 20px;">Aponte a câmera do seu celular para o QR Code ou use o código abaixo.</p>

                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="data:image/png;base64, <?php echo $pix_qr_code_base64; ?>" alt="QR Code Pix" style="max-width: 100%; height: auto; border: 1px solid #ccc; padding: 10px;">
                </div>

                <div class="form-group">
                    <label for="pix-copia-cola" style="margin-bottom: 5px; font-weight: 500;">Pix Copia e Cola:</label>
                    <textarea id="pix-copia-cola" class="form-control" rows="4" readonly><?php echo $pix_qr_code; ?></textarea>
                </div>
                
                <button class="btn" onclick="copiarPix()">Copiar Código</button>
                <div class="alert" style="background-color: #d1ecf1; color: #0c5460; margin-top: 20px; font-size: 14px;">
                    <i class="fas fa-spinner fa-spin"></i> Aguardando confirmação de pagamento...
                </div>
            </div>

            <div id="success-screen" style="display: none; text-align: center;">
                <div style="font-size: 80px; color: var(--success-color);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="card-title" style="margin-top: 20px; color: var(--success-color);">Pagamento Aprovado!</h1>
                <p>Sua assinatura foi renovada com sucesso. Obrigado!</p>
                <a href="dashboard.php" class="btn" style="background-color: var(--success-color);">Voltar ao Dashboard</a>
            </div>
        </div>
    </div>

<script>
function copiarPix() {
    var copyText = document.getElementById("pix-copia-cola");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("Código Pix copiado!");
}

// Lógica de verificação de pagamento
document.addEventListener('DOMContentLoaded', function() {
    const vencimentoOriginal = '<?php echo $vencimento_atual; ?>';
    let attempts = 0;
    const maxAttempts = 60; // Verifica por 5 minutos (60 tentativas * 5 segundos)

    const intervalId = setInterval(() => {
        attempts++;
        if (attempts > maxAttempts) {
            clearInterval(intervalId);
            return;
        }

        let formData = new FormData();
        formData.append('vencimento_original', vencimentoOriginal);

        fetch('verificar_pagamento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'aprovado') {
                clearInterval(intervalId); // Para de verificar
                // Esconde a tela de pagamento e mostra a de sucesso
                document.getElementById('payment-screen').style.display = 'none';
                document.getElementById('success-screen').style.display = 'block';
            }
        })
        .catch(error => console.error('Erro ao verificar pagamento:', error));

    }, 5000); // Verifica a cada 5 segundos
});
</script>
</body>
</html>