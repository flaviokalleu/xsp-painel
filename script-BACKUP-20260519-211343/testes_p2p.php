<?php
// Inclui o cabeçalho e menu do seu painel principal
require_once 'menu.php';
require_once './api/controles/db.php'; 

$testes_p2p = [];
$template_p2p_atual = '';

$conexao = conectar_bd();
if ($conexao) {
    try {
        $stmt_template = $conexao->prepare("SELECT valor FROM configuracoes WHERE chave = 'p2p_message_template' LIMIT 1");
        $stmt_template->execute();
        $template_p2p_atual = $stmt_template->fetchColumn();

        $sql = "SELECT id, usuario, name, whatsapp, Vencimento FROM clientes WHERE is_p2p = 1 AND plano = 'Teste P2P'";
        $params = [];

        if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0) {
            $sql .= " AND admin_id = :admin_id";
            $params[':admin_id'] = $_SESSION['admin_id'];
        }
        
        $sql .= " ORDER BY id DESC";
        $stmt = $conexao->prepare($sql);
        $stmt->execute($params);
        $testes_p2p = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {}
}
?>

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3 p-4">
        
        <h4 class="card-title text-primary d-flex justify-content-between align-items-center mb-4">
            <span class="fw-bold"> Testes P2P</span>
            <form action="action_p2p_gerar_teste.php" method="POST" class="m-0">
                <button type="submit" class="btn btn-primary rounded-pill">
                    <i class="fas fa-plus me-2"></i> Gerar Novo Teste
                </button>
            </form>
        </h4>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert <?php echo strpos($_SESSION['msg_type'], 'success') !== false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['mensagem']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php unset($_SESSION['mensagem']); unset($_SESSION['msg_type']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="display table table-striped table-hover border" style="width: 100%;">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>#</th>
                        <th>Nome / Descrição</th>
                        <th>Código (Usuário)</th>
                        <th>Vencimento</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($testes_p2p)): ?>
                        <?php foreach ($testes_p2p as $teste): ?>
                            <tr>
                                <td><?php echo $teste['id']; ?></td>
                                <td><?php echo htmlspecialchars($teste['name']); ?></td>
                                <td><?php echo htmlspecialchars($teste['usuario']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($teste['Vencimento'])); ?></td>
                                <td class="text-end">
                                    <button class="btn btn-info btn-sm rounded-circle" style="width: 32px; height: 32px; line-height: 1.5;" title="Ver Informações"
                                        onclick="exibirModalP2P('Informações do Teste', 
                                        `<?php 
                                            $venc_fmt = date('d/m/Y H:i', strtotime($teste['Vencimento']));
                                            echo addslashes(str_replace(['#cliente#', '#codigo#', '#vencimento#'], [htmlspecialchars($teste['name']), $teste['usuario'], $venc_fmt], $template_p2p_atual)); 
                                        ?>`)">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <a href="p2p_renovar_teste_form.php?id=<?php echo $teste['id']; ?>" class="btn btn-warning btn-sm rounded-circle" style="width: 32px; height: 32px; line-height: 1.5;" title="Renovar / Converter Teste">
                                        <i class="fas fa-sync-alt"></i>
                                    </a>

                                    <a href="action_p2p_excluir.php?id=<?php echo $teste['id']; ?>&from=testes_p2p.php" class="btn btn-danger btn-sm rounded-circle" style="width: 32px; height: 32px; line-height: 1.5;" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este teste?');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Nenhum teste P2P encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php
    if (isset($_SESSION['show_p2p_modal_message'])) {
        echo "exibirModalP2P('" . addslashes($_SESSION['show_p2p_modal_title']) . "', `" . addslashes($_SESSION['show_p2p_modal_message']) . "`);";
        unset($_SESSION['show_p2p_modal_message']);
        unset($_SESSION['show_p2p_modal_title']);
    }
    ?>
});
</script>