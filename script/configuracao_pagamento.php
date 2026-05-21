<?php
session_start();
require_once("menu.php");
require_once("chatbot_integrado_funcoes.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
$admin_id = $_SESSION['admin_id'];
$feedback = '';

// Lógica para guardar as credenciais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_credenciais'])) {
    $token = $_POST['mp_access_token'] ?? '';
    $secret = $_POST['mp_webhook_secret'] ?? '';
    if (saveAdminPaymentConfig($admin_id, $token, $secret)) {
        $feedback = "<div class='alert alert-success'>Credenciais do Mercado Pago guardadas com sucesso!</div>";
    } else {
        $feedback = "<div class='alert alert-danger'>Ocorreu um erro ao guardar as credenciais.</div>";
    }
}

$config = getAdminPaymentConfig($admin_id);
?>

<!-- CSS para o design profissional -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
    .page-content {
        font-family: 'Poppins', sans-serif;
    }
    .payment-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .payment-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-bottom: none;
    }
    .payment-card .card-header h3 {
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
    }
    .payment-card .card-header i {
        font-size: 1.5rem;
        margin-right: 1rem;
    }
    .form-label {
        font-weight: 500;
        color: #555;
    }
    .form-control {
        border-radius: 8px;
        padding: 12px 15px;
        border: 1px solid #ddd;
    }
    .btn-save {
        background: linear-gradient(135deg, #1ed760 0%, #28b485 100%);
        border: none;
        font-weight: 600;
        padding: 12px 30px;
        transition: all 0.3s ease;
        color: white;
    }
    .btn-save:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        color: white;
    }
</style>

<div class="page-content" style="padding: 20px;">
    
    <?php echo $feedback; ?>

    <div class="card payment-card">
        <div class="card-header">
            <h3><i class="fas fa-credit-card"></i> Configuração de Pagamento</h3>
        </div>
        <form method="POST" action="configuracao_pagamento.php">
            <div class="card-body p-4">
                <p class="text-muted mb-4">Insira as suas credenciais do Mercado Pago para ativar a renovação automática para os seus clientes.</p>
                
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong>Atenção:</strong> As suas credenciais são secretas. Pode encontrá-las no seu painel do Mercado Pago em <strong>Seu negócio > Configurações > Credenciais de produção</strong>.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="mp_access_token" class="form-label">Access Token de Produção</label>
                    <input type="text" class="form-control" id="mp_access_token" name="mp_access_token" value="<?php echo htmlspecialchars($config['mp_access_token'] ?? ''); ?>" placeholder="APP_USR-...">
                </div>
                <div class="mb-3">
                    <label for="mp_webhook_secret" class="form-label">Assinatura Secreta do Webhook (Secret Key)</label>
                    <input type="text" class="form-control" id="mp_webhook_secret" name="mp_webhook_secret" value="<?php echo htmlspecialchars($config['mp_webhook_secret'] ?? ''); ?>" placeholder="A sua chave secreta para validar notificações">
                </div>
            </div>
            <div class="card-footer text-end bg-light p-3">
                <button type="submit" name="salvar_credenciais" class="btn btn-save">
                    <i class="fas fa-save me-2"></i>Guardar Configurações
                </button>
            </div>
        </form>
    </div>
</div>
