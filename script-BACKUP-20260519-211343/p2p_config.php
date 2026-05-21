<?php
// Inclui o cabeçalho e menu do seu painel principal
include 'menu.php'; 

require_once './api/controles/db.php';

$conexao = conectar_bd();
$mensagem = '';
$msg_type = '';

// Lógica para SALVAR as configurações se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nova_senha_p2p = $_POST['p2p_password'];
        $novo_tempo_teste = $_POST['p2p_test_hours'];
        $novo_template = $_POST['p2p_template']; // Novo campo

        // Atualiza a senha na tabela `settings`
        $stmt_senha = $conexao->prepare("UPDATE settings SET setting_value = :valor WHERE setting_name = 'p2p_default_password'");
        $stmt_senha->bindParam(':valor', $nova_senha_p2p);
        $stmt_senha->execute();

        // Atualiza a duração do teste na tabela `configuracoes`
        $stmt_teste = $conexao->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = 'p2p_test_duration_hours'");
        $stmt_teste->bindParam(':valor', $novo_tempo_teste);
        $stmt_teste->execute();

        // Salva o novo template de mensagem
        $stmt_template = $conexao->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = 'p2p_message_template'");
        $stmt_template->bindParam(':valor', $novo_template);
        $stmt_template->execute();

        $mensagem = 'Configurações salvas com sucesso!';
        $msg_type = 'alert-success';

    } catch (PDOException $e) {
        $mensagem = 'Erro ao salvar as configurações: ' . $e->getMessage();
        $msg_type = 'alert-danger';
    }
}

// Lógica para BUSCAR as configurações atuais para exibir no formulário
$configuracoes = [];
$senha_p2p_atual = '';
$template_p2p_atual = '';
try {
    // Busca a senha P2P da tabela `settings`
    $stmt_senha = $conexao->prepare("SELECT setting_value FROM settings WHERE setting_name = 'p2p_default_password' LIMIT 1");
    $stmt_senha->execute();
    $senha_p2p_atual = $stmt_senha->fetchColumn();

    // Busca as outras configs da tabela `configuracoes`
    $stmt_configs = $conexao->prepare("SELECT chave, valor FROM configuracoes WHERE chave IN ('p2p_test_duration_hours', 'p2p_message_template')");
    $stmt_configs->execute();
    $outras_configs = $stmt_configs->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $configuracoes['p2p_test_duration_hours'] = $outras_configs['p2p_test_duration_hours'] ?? 4;
    $template_p2p_atual = $outras_configs['p2p_message_template'] ?? '';

} catch (PDOException $e) {
    $mensagem = 'Erro ao carregar as configurações: ' . $e->getMessage();
    $msg_type = 'alert-danger';
}
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header"><h4 class="m-0"><i class="fas fa-cogs"></i> Configurações do Módulo P2P</h4></div>
        <div class="card-body">
            
            <?php if (!empty($mensagem)): ?>
                <div class="alert <?php echo $msg_type === 'alert-success' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensagem; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="p2p_config.php">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="p2p_password" class="form-label fw-bold">Senha Global P2P</label>
                            <input type="text" id="p2p_password" name="p2p_password" class="form-control" value="<?php echo htmlspecialchars($senha_p2p_atual ?? ''); ?>" required>
                            <div class="form-text">Esta é a senha que o aplicativo P2P usa para autenticar.</div>
                        </div>

                        <div class="mb-3">
                            <label for="p2p_test_hours" class="form-label fw-bold">Duração do Teste P2P (em horas)</label>
                            <input type="number" id="p2p_test_hours" name="p2p_test_hours" class="form-control" value="<?php echo htmlspecialchars($configuracoes['p2p_test_duration_hours'] ?? '4'); ?>" required>
                            <div class="form-text">Quantidade de horas que um teste P2P ficará ativo.</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="p2p_template" class="form-label fw-bold">Template de Mensagem P2P</label>
                            <textarea id="p2p_template" name="p2p_template" class="form-control" rows="8"><?php echo htmlspecialchars($template_p2p_atual); ?></textarea>
                            <div class="form-text">Edite a mensagem que será gerada ao criar um cliente ou teste.</div>
                        </div>
                        <div class="p-3 bg-light rounded border">
                            <h6 class="fw-bold">Variáveis Disponíveis:</h6>
                            <p class="mb-1"><small>`#cliente#` - Nome do cliente/descrição.</small></p>
                            <p class="mb-1"><small>`#codigo#` - Código de acesso P2P.</small></p>
                            <p class="mb-0"><small>`#vencimento#` - Data de vencimento.</small></p>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar Todas as Alterações</button>
                <a href="codigos_p2p.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </form>
        </div>
    </div>
</div>

<?php
// include 'footer.php'; 
?>