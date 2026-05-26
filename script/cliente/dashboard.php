<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['client_loggedin']) || $_SESSION['client_loggedin'] !== true) {
    header('Location: index.php');
    exit();
}
require_once('../api/controles/db.php');
$conexao = conectar_bd();

$stmt = $conexao->prepare("SELECT name, usuario, Vencimento, plano, admin_id FROM clientes WHERE id = ?");
$stmt->execute([$_SESSION['client_id']]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

$vencido = strtotime($cliente['Vencimento']) < time();
$status_texto = $vencido ? 'Plano Vencido' : 'Plano Ativo';
$status_icon = $vencido ? 'fa-times-circle' : 'fa-check-circle';
$status_class = $vencido ? 'status-vencido' : 'status-ativo';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Área do Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="client-container">
        <header class="client-header">
            <h1>Olá, <?php echo htmlspecialchars($cliente['name'] ?: $cliente['usuario']); ?>!</h1>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </header>

        <main class="client-content">
            <div class="info-card status-card <?php echo $status_class; ?>">
                <div class="icon"><i class="fas <?php echo $status_icon; ?>"></i></div>
                <div class="text-content">
                    <div class="label">Status da Assinatura</div>
                    <div class="value"><?php echo $status_texto; ?></div>
                    <div class="date">Válido até: <?php echo date('d/m/Y', strtotime($cliente['Vencimento'])); ?></div>
                </div>
            </div>

            <?php if (isset($_SESSION['payment_error'])): ?>
                <div class="alert-danger" style="margin-bottom: 25px; padding: 15px; border-radius: 8px;"><?php echo $_SESSION['payment_error']; unset($_SESSION['payment_error']); ?></div>
            <?php endif; ?>

            <div class="info-card payment-section">
                <h3>Renovar Assinatura</h3>
                <div class="payment-options">
                    <div class="payment-option-card">
                        <div class="icon" style="color: #ffb061;"><i class="fas fa-qrcode"></i></div>
                        <h4>Pagar com Pix</h4>
                        <p>Aprovação imediata. Escaneie o QR Code ou use o código Copia e Cola.</p>
                        <form action="gerar_pagamento_pix.php" method="POST">
                            <input type="hidden" name="plano_id" value="<?php echo htmlspecialchars($cliente['plano']); ?>">
                            <button type="submit" class="btn-pay btn-pix">Gerar Pix</button>
                        </form>
                    </div>
                    <div class="payment-option-card">
                        <div class="icon" style="color: #76b852;"><i class="fas fa-credit-card"></i></div>
                        <h4>Cartão / Outros</h4>
                        <p>Pague com cartão de crédito ou outros métodos disponíveis com segurança.</p>
                        <form action="gerar_pagamento.php" method="POST">
                            <input type="hidden" name="plano_id" value="<?php echo htmlspecialchars($cliente['plano']); ?>">
                            <button type="submit" class="btn-pay btn-card">Pagar Agora</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>