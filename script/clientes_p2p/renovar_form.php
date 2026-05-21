<?php
require_once '../api/controles/db.php';

$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) {
    header('Location: index.php');
    exit();
}

$conexao = conectar_bd();
$cliente = null;
$planos = [];

if ($conexao) {
    // Busca os dados do cliente para exibir na tela
    $stmt_cliente = $conexao->prepare("SELECT usuario, name, Vencimento FROM clientes WHERE id = :id");
    $stmt_cliente->bindParam(':id', $cliente_id);
    $stmt_cliente->execute();
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    // Busca os planos para o menu de seleção
    // IMPORTANTE: Esta parte assume que sua tabela `planos` tem a coluna `duracao_dias`
    $stmt_planos = $conexao->prepare("SELECT id, nome, valor, duracao_dias FROM planos ORDER BY nome ASC");
    $stmt_planos->execute();
    $planos = $stmt_planos->fetchAll(PDO::FETCH_ASSOC);
}

if (!$cliente) {
    // Se não encontrou o cliente, volta para a página principal
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renovar Cliente P2P</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Renovar Cliente P2P</h1>

        <div style="margin-bottom: 20px; font-size: 1.1em;">
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($cliente['name']); ?></p>
            <p><strong>Código:</strong> <?php echo htmlspecialchars($cliente['usuario']); ?></p>
            <p><strong>Vencimento Atual:</strong> <?php echo date('d/m/Y H:i', strtotime($cliente['Vencimento'])); ?></p>
        </div>

        <form action="action_renovar.php" method="POST">
            <input type="hidden" name="id_cliente" value="<?php echo $cliente_id; ?>">

            <div class="form-group">
                <label for="plano">Selecionar Plano de Renovação</label>
                <select id="plano" name="plano_info" class="form-control" required>
                    <option value="">-- Escolha um plano --</option>
                    <?php foreach ($planos as $plano): ?>
                        <option value="<?php echo $plano['id'] . '|' . $plano['duracao_dias']; ?>">
                            <?php echo htmlspecialchars($plano['nome']) . " (Adiciona " . $plano['duracao_dias'] . " dias)"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-success">Confirmar Renovação</button>
                <a href="index.php" class="btn btn-danger">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>