<?php
// Inclui o cabeçalho e menu do seu painel principal
include 'menu.php';

require_once './api/controles/db.php';

$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) {
    header('Location: codigos_p2p.php');
    exit();
}

$conexao = conectar_bd();
$cliente = null;
$planos = [];

if ($conexao) {
    // --- SEGURANÇA APLICADA AQUI ---
    $sql = "SELECT id, usuario, name, Vencimento FROM clientes WHERE id = :id AND is_p2p = 1";
    $params = [':id' => $cliente_id];

    // Se for revendedor, verifica se o cliente pertence a ele
    if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0) {
        $sql .= " AND admin_id = :admin_id";
        $params[':admin_id'] = $_SESSION['admin_id'];
    }

    $stmt_cliente = $conexao->prepare($sql);
    $stmt_cliente->execute($params);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    // Se o cliente não foi encontrado (ou não tem permissão), redireciona
    if (!$cliente) {
        $_SESSION['mensagem'] = "Erro: Cliente não encontrado ou você não tem permissão para acessá-lo.";
        $_SESSION['msg_type'] = "alert-error";
        header('Location: codigos_p2p.php');
        exit();
    }

    // Busca os planos para o menu de seleção
    $stmt_planos = $conexao->prepare("SELECT id, nome, duracao_dias FROM planos ORDER BY nome ASC");
    $stmt_planos->execute();
    $planos = $stmt_planos->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="m-0"><i class="fas fa-sync-alt"></i> Renovar Código P2P</h4>
        </div>
        <div class="card-body">
            <div class="mb-4 p-3 bg-light rounded">
                <p class="mb-1"><strong>Cliente:</strong> <?php echo htmlspecialchars($cliente['name']); ?></p>
                <p class="mb-1"><strong>Código:</strong> <?php echo htmlspecialchars($cliente['usuario']); ?></p>
                <p class="mb-0"><strong>Vencimento Atual:</strong> <?php echo date('d/m/Y H:i', strtotime($cliente['Vencimento'])); ?></p>
            </div>

            <form action="action_p2p_renovar.php" method="POST">
                <input type="hidden" name="id_cliente" value="<?php echo $cliente_id; ?>">
                <div class="mb-3">
                    <label for="plano" class="form-label">Selecionar Plano de Renovação</label>
                    <select id="plano" name="plano_info" class="form-control" required>
                        <option value="">-- Escolha um plano --</option>
                        <?php foreach ($planos as $plano): ?>
                            <option value="<?php echo $plano['id'] . '|' . $plano['duracao_dias']; ?>">
                                <?php echo htmlspecialchars($plano['nome']) . " (Adiciona " . $plano['duracao_dias'] . " dias)"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Confirmar Renovação</button>
                <a href="codigos_p2p.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </form>
        </div>
    </div>
</div>
<?php
// include 'footer.php'; 
?>