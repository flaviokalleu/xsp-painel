<?php
require_once 'menu.php'; // Inclui seu menu padrão

if (!isset($_SESSION['admin_id'])) {
    echo '<div class="page-content">Acesso negado.</div>';
    exit;
}

require_once './api/controles/db.php';
$pdo = conectar_bd();
$admin_id = $_SESSION['admin_id'];
$feedback = '';
$credenciais = ['mp_public_key' => '', 'mp_access_token' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $public_key = $_POST['public_key'] ?? '';
    $access_token = $_POST['access_token'] ?? '';

    // CORREÇÃO: Trocado 'revendedores' por 'admin'
    $stmt = $pdo->prepare("UPDATE admin SET mp_public_key = ?, mp_access_token = ? WHERE id = ?");
    if ($stmt->execute([$public_key, $access_token, $admin_id])) {
        $feedback = "<div class='alert alert-success'>Credenciais salvas com sucesso!</div>";
    } else {
        $feedback = "<div class='alert alert-danger'>Erro ao salvar as credenciais.</div>";
    }
}

// CORREÇÃO: Trocado 'revendedores' por 'admin'
$stmt = $pdo->prepare("SELECT mp_public_key, mp_access_token FROM admin WHERE id = ?");
$stmt->execute([$admin_id]);
$credenciais = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0"><i class="fas fa-dollar-sign text-primary"></i> Configurar Pagamento Automático</h4>
            </div>
        </div>
    </div>

    <?php echo $feedback; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Credenciais do Mercado Pago</h5>
                    <p>Insira suas credenciais do Mercado Pago para receber pagamentos de renovação dos seus clientes diretamente na sua conta.</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="public_key" class="form-label">Public Key</label>
                            <input type="text" class="form-control" name="public_key" id="public_key" value="<?php echo htmlspecialchars($credenciais['mp_public_key'] ?? ''); ?>" placeholder="APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                        </div>
                        <div class="mb-3">
                            <label for="access_token" class="form-label">Access Token</label>
                            <input type="password" class="form-control" name="access_token" id="access_token" value="<?php echo htmlspecialchars($credenciais['mp_access_token'] ?? ''); ?>" placeholder="APP_USR-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            <small class="form-text text-muted">Seu Access Token é confidencial. Não o compartilhe.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar Credenciais</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                 <div class="card-body">
                    <h5 class="card-title">Onde encontrar?</h5>
                    <ol>
                        <li>Acesse o <a href="https://www.mercadopago.com.br/developers" target="_blank">Painel de Desenvolvedores</a>.</li>
                        <li>Vá em "Suas Aplicações".</li>
                        <li>Selecione ou crie uma aplicação.</li>
                        <li>Vá em "Credenciais de Produção".</li>
                        <li>Copie e cole a "Public key" e o "Access token".</li>
                    </ol>
                 </div>
            </div>
        </div>
    </div>
</div>