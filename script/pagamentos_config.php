<?php
require_once 'menu.php';
require_once './api/controles/db.php';

$conexao = conectar_bd();
$user_id = $_SESSION['admin_id'];
$mensagem = '';
$msg_type = '';
$config = [];

// Lógica para SALVAR os dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mp_access_token = trim($_POST['mp_access_token']);
    $mp_signing_secret = trim($_POST['mp_signing_secret']); // Novo campo

    // Gera ou mantém o token do webhook
    $stmt_check = $conexao->prepare("SELECT webhook_token FROM revendedor_configuracoes WHERE revendedor_id = ?");
    $stmt_check->execute([$user_id]);
    $existing_token = $stmt_check->fetchColumn();
    $webhook_token = $existing_token ?: md5($user_id . time());

    if ($existing_token) {
        // Se já existe, atualiza (UPDATE)
        $stmt_save = $conexao->prepare("UPDATE revendedor_configuracoes SET mp_access_token = ?, mp_signing_secret = ? WHERE revendedor_id = ?");
        $stmt_save->execute([$mp_access_token, $mp_signing_secret, $user_id]);
    } else {
        // Se não existe, insere (INSERT)
        $stmt_save = $conexao->prepare("INSERT INTO revendedor_configuracoes (revendedor_id, mp_access_token, mp_signing_secret, webhook_token) VALUES (?, ?, ?, ?)");
        $stmt_save->execute([$user_id, $mp_access_token, $mp_signing_secret, $webhook_token]);
    }
    $mensagem = 'Configurações de pagamento salvas com sucesso!';
    $msg_type = 'alert-success';
}

// Lógica para BUSCAR os dados atuais
$stmt_get = $conexao->prepare("SELECT mp_access_token, mp_signing_secret, webhook_token FROM revendedor_configuracoes WHERE revendedor_id = ?");
$stmt_get->execute([$user_id]);
$config = $stmt_get->fetch(PDO::FETCH_ASSOC);

$webhook_url = "https://" . $_SERVER['HTTP_HOST'] . "/webhook_mp.php?token=" . ($config['webhook_token'] ?? '');
?>

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3 p-4">
        <h4 class="card-title text-primary fw-bold mb-4"><i class="fas fa-dollar-sign me-2"></i> Configurações de Pagamento Automático</h4>

        <?php if (!empty($mensagem)): ?>
            <div class="alert <?php echo $msg_type; ?> alert-dismissible fade show" role="alert"><?php echo $mensagem; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        <?php endif; ?>

        <form method="POST" action="pagamentos_config.php">
            <div class="mb-3">
                <label for="mp_access_token" class="form-label fw-bold">Mercado Pago - Access Token</label>
                <input type="text" id="mp_access_token" name="mp_access_token" class="form-control" value="<?php echo htmlspecialchars($config['mp_access_token'] ?? ''); ?>" placeholder="APP_USR-...">
                <div class="form-text">Insira seu "Access Token" de Produção do Mercado Pago.</div>
            </div>

            <div class="mb-3">
                <label for="mp_signing_secret" class="form-label fw-bold">Mercado Pago - Chave Secreta do Webhook</label>
                <input type="text" id="mp_signing_secret" name="mp_signing_secret" class="form-control" value="<?php echo htmlspecialchars($config['mp_signing_secret'] ?? ''); ?>" placeholder="Cole a chave secreta gerada no Mercado Pago aqui">
                <div class="form-text">Esta chave é usada para verificar a autenticidade das notificações de pagamento.</div>
            </div>
            
            <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i> Salvar Configurações</button>
        </form>

        <?php if (!empty($config['webhook_token'])): ?>
        <hr class="my-4">
        <div>
            <h5 class="fw-bold">Sua URL de Webhook</h5>
            <p>Copie a URL abaixo e configure-a no seu painel do Mercado Pago, na seção "Webhooks". Ative o evento de **Pagamentos** e insira a Chave Secreta.</p>
            <div class="input-group">
                <input type="text" class="form-control" value="<?php echo $webhook_url; ?>" id="webhook-url" readonly>
                <button class="btn btn-outline-secondary" onclick="copiarWebhook()">Copiar</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copiarWebhook() {
    var copyText = document.getElementById("webhook-url");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("URL do Webhook copiada!");
}
</script>