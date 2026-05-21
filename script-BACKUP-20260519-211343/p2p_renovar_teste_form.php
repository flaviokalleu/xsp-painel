<?php
// Inclui o cabeçalho e menu do seu painel principal
require_once 'menu.php';
require_once './api/controles/db.php';

$id_teste = $_GET['id'] ?? null;
if (!$id_teste) {
    header('Location: testes_p2p.php');
    exit();
}

$conexao = conectar_bd();
$teste = null;
$planos = []; // Vamos usar os planos normais para a conversão

if ($conexao) {
    // Busca os dados do teste para exibir na tela
    $sql = "SELECT id, usuario, name, Vencimento FROM clientes WHERE id = :id AND plano = 'Teste P2P'";
    $params = [':id' => $id_teste];

    // Se for revendedor, garante que ele só possa ver seu próprio teste
    if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0) {
        $sql .= " AND admin_id = :admin_id";
        $params[':admin_id'] = $_SESSION['admin_id'];
    }

    $stmt_teste = $conexao->prepare($sql);
    $stmt_teste->execute($params);
    $teste = $stmt_teste->fetch(PDO::FETCH_ASSOC);

    if (!$teste) {
        $_SESSION['mensagem'] = "Erro: Teste não encontrado ou você não tem permissão.";
        $_SESSION['msg_type'] = "alert-error";
        header('Location: testes_p2p.php');
        exit();
    }

    // Busca os planos mensais, trimestrais, etc.
    $stmt_planos = $conexao->prepare("SELECT id, nome, duracao_dias FROM planos ORDER BY nome ASC");
    $stmt_planos->execute();
    $planos = $stmt_planos->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3 p-4">
        <h4 class="card-title text-primary mb-4 fw-bold"><i class="fas fa-sync-alt me-2"></i> Renovar / Converter Teste P2P</h4>
        
        <div class="mb-4 p-3 bg-light rounded border">
            <p class="mb-1"><strong>Cliente:</strong> <?php echo htmlspecialchars($teste['name']); ?></p>
            <p class="mb-1"><strong>Código:</strong> <?php echo htmlspecialchars($teste['usuario']); ?></p>
            <p class="mb-0"><strong>Vencimento Atual:</strong> <?php echo date('d/m/Y H:i', strtotime($teste['Vencimento'])); ?></p>
        </div>

        <form action="action_p2p_renovar.php" method="POST">
            <input type="hidden" name="id_cliente" value="<?php echo $id_teste; ?>">
            <div class="mb-3">
                <label for="plano" class="form-label fw-bold">Selecione um Plano para Converter/Renovar</label>
                <select id="plano" name="plano_info" class="form-control" required>
                    <option value="">-- Escolha um plano (isso converterá o teste em um cliente) --</option>
                    <?php foreach ($planos as $plano): ?>
                        <option value="<?php echo $plano['id'] . '|' . $plano['duracao_dias']; ?>">
                            <?php echo htmlspecialchars($plano['nome']) . " (Adiciona " . $plano['duracao_dias'] . " dias)"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Ao escolher um plano, o teste será convertido em um cliente fixo e movido para a lista de "Gerenciar Clientes".</div>
            </div>

            <button type="submit" class="btn btn-success"><i class="fas fa-check me-2"></i> Confirmar Renovação</button>
            <a href="testes_p2p.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
        </form>
    </div>
</div>