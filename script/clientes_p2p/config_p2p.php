<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../api/controles/db.php';

$conexao = conectar_bd();
$mensagem = '';
$msg_type = '';

// Lógica para SALVAR as configurações se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nova_senha = $_POST['p2p_password'];
        $novo_tempo_teste = $_POST['p2p_test_hours'];

        // Atualiza a senha
        $stmt_senha = $conexao->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = 'p2p_global_password'");
        $stmt_senha->bindParam(':valor', $nova_senha);
        $stmt_senha->execute();

        // Atualiza a duração do teste
        $stmt_teste = $conexao->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = 'p2p_test_duration_hours'");
        $stmt_teste->bindParam(':valor', $novo_tempo_teste);
        $stmt_teste->execute();

        $mensagem = 'Configurações salvas com sucesso!';
        $msg_type = 'alert-success';

    } catch (PDOException $e) {
        $mensagem = 'Erro ao salvar as configurações: ' . $e->getMessage();
        $msg_type = 'alert-error';
    }
}

// Lógica para BUSCAR as configurações atuais para exibir no formulário
$configuracoes = [];
try {
    $stmt = $conexao->prepare("SELECT chave, valor FROM configuracoes WHERE chave IN ('p2p_global_password', 'p2p_test_duration_hours')");
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($resultados as $resultado) {
        $configuracoes[$resultado['chave']] = $resultado['valor'];
    }
} catch (PDOException $e) {
    $mensagem = 'Erro ao carregar as configurações: ' . $e->getMessage();
    $msg_type = 'alert-error';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações P2P</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-cogs"></i> Configurações do Módulo P2P</h1>

        <?php if (!empty($mensagem)): ?>
            <div class="alert <?php echo $msg_type; ?>"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <form method="POST" action="config_p2p.php">
            <div class="form-group">
                <label for="p2p_password">Senha Global P2P</label>
                <input type="text" id="p2p_password" name="p2p_password" class="form-control" value="<?php echo htmlspecialchars($configuracoes['p2p_global_password'] ?? ''); ?>" required>
                <small style="color: #bdc3c7; margin-top: 5px; display: block;">Esta será a senha para todos os usuários P2P criados.</small>
            </div>

            <div class="form-group">
                <label for="p2p_test_hours">Duração do Teste P2P (em horas)</label>
                <input type="number" id="p2p_test_hours" name="p2p_test_hours" class="form-control" value="<?php echo htmlspecialchars($configuracoes['p2p_test_duration_hours'] ?? '4'); ?>" required>
                <small style="color: #bdc3c7; margin-top: 5px; display: block;">Quantidade de horas que um teste P2P ficará ativo.</small>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-success">Salvar Alterações</button>
                <a href="index.php" class="btn btn-primary">Voltar para Clientes</a>
            </div>
        </form>
    </div>
</body>
</html>