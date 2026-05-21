<?php
// Inclui o cabeçalho e menu do seu painel principal
include 'menu.php';

// --- LÓGICA PARA BUSCAR OS PLANOS ---
require_once './api/controles/db.php';
$conexao = conectar_bd();
$planos = [];
if ($conexao) {
    // Assumindo que sua tabela `planos` tem a coluna `duracao_dias`
    $stmt = $conexao->prepare("SELECT id, nome, valor, duracao_dias FROM planos ORDER BY nome ASC");
    $stmt->execute();
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="m-0"><i class="fas fa-plus"></i> Adicionar Novo Código P2P</h4>
        </div>
        <div class="card-body">
            <form action="action_p2p_criar.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Nome / Descrição</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Ex: Cliente João Silva" required>
                </div>
                <div class="mb-3">
                    <label for="whatsapp" class="form-label">WhatsApp (Opcional)</label>
                    <input type="text" id="whatsapp" name="whatsapp" class="form-control" placeholder="(11) 98765-4321">
                </div>
                <div class="mb-3">
                    <label for="plano" class="form-label">Selecionar Pacote</label>
                    <select id="plano" name="plano_info" class="form-control" required>
                        <option value="">-- Escolha um plano --</option>
                        <?php foreach ($planos as $plano): ?>
                            <option value="<?php echo $plano['id'] . '|' . $plano['duracao_dias']; ?>">
                                <?php echo htmlspecialchars($plano['nome']) . " (" . htmlspecialchars($plano['valor']) . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Criar Usuário</button>
                <a href="codigos_p2p.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </form>
        </div>
    </div>
</div>

<?php
// include 'footer.php'; 
?>