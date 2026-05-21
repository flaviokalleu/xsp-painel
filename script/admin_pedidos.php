<?php
session_start();
// Medida de segurança: Apenas o admin principal pode acessar esta página
if (!isset($_SESSION['nivel_admin']) || $_SESSION['nivel_admin'] != 1) {
    die("Acesso negado. Você não tem permissão para acessar esta página.");
}

require_once("menu.php");

// ======================================================================
//      LINHA DE CORREÇÃO ADICIONADA AQUI
//      Inclui a conexão com o banco de dados que estava faltando.
// ======================================================================
require_once("./api/controles/db.php");
$conexao = conectar_bd();

// Lógica para as ações de gerenciamento via AJAX
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'];

    try {
        if ($action === 'marcar_atendido' && $id > 0) {
            $conexao->prepare("UPDATE pedidos_vod SET status = 'atendido' WHERE id = ?")->execute([$id]);
        } elseif ($action === 'remover_atendidos') {
            $conexao->exec("DELETE FROM pedidos_vod WHERE status = 'atendido'");
        } elseif ($action === 'remover_pendentes') {
            $conexao->exec("DELETE FROM pedidos_vod WHERE status = 'pendente'");
        } elseif ($action === 'remover_todos') {
            $conexao->exec("TRUNCATE TABLE pedidos_vod");
        }
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Busca TODOS os pedidos para o admin
$pedidos_pendentes = $conexao->query("SELECT * FROM pedidos_vod WHERE status = 'pendente' ORDER BY data_pedido DESC");
$pedidos_atendidos = $conexao->query("SELECT * FROM pedidos_vod WHERE status = 'atendido' ORDER BY data_pedido DESC");
?>

<h4 class="align-items-center d-flex justify-content-between mb-4 text-muted text-uppercase">
    Gerenciamento de Pedidos
</h4>

<div class="card mb-4">
    <div class="card-header fw-bold" style="color: var(--text-primary);"><i class="fas fa-clock"></i> Pedidos Pendentes</div>
    <div class="card-body">
        <ul id="lista-pendentes" class="list-group">
            <?php if ($pedidos_pendentes->rowCount() > 0): ?>
                <?php while ($pedido = $pedidos_pendentes->fetch()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="background-color: var(--bg-card); color: var(--text-primary);" data-id="<?php echo $pedido['id']; ?>">
                        <div>
                            <strong><?php echo htmlspecialchars($pedido['titulo']); ?></strong> (<?php echo ucfirst($pedido['tipo']); ?>)
                            <br><small class="text-muted">Pedido por: <?php echo htmlspecialchars($pedido['nome_admin']); ?></small>
                        </div>
                        <button class="btn btn-sm btn-success marcar-atendido" title="Marcar como Atendido">✔️</button>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <p id="msg-pendentes">Nenhum pedido pendente encontrado.</p>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header fw-bold" style="color: var(--text-primary);"><i class="fas fa-check-circle"></i> Pedidos Atendidos</div>
    <div class="card-body">
        <ul id="lista-atendidos" class="list-group">
            <?php if ($pedidos_atendidos->rowCount() > 0): ?>
                <?php while ($pedido = $pedidos_atendidos->fetch()): ?>
                    <li class="list-group-item text-muted" style="background-color: var(--bg-card); text-decoration: line-through;">
                        <strong><?php echo htmlspecialchars($pedido['titulo']); ?></strong> (<?php echo ucfirst($pedido['tipo']); ?>)
                        - <small>Pedido por: <?php echo htmlspecialchars($pedido['nome_admin']); ?></small>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <p id="msg-atendidos">Nenhum pedido atendido encontrado.</p>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header fw-bold" style="color: var(--text-primary);"><i class="fas fa-trash"></i> Gerenciamento de Pedidos</div>
    <div class="card-body">
        <p>Utilize as opções abaixo para limpar os pedidos do sistema.</p>
        <button class="btn btn-danger manage-btn" data-action="remover_todos">Remover Todos os Pedidos</button>
        <button class="btn btn-warning manage-btn" data-action="remover_pendentes">Remover Pedidos Pendentes</button>
        <button class="btn btn-info manage-btn" data-action="remover_atendidos">Remover Pedidos Atendidos</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Marcar como atendido
    $('#lista-pendentes').on('click', '.marcar-atendido', function() {
        const btn = $(this);
        const item = btn.closest('li');
        const id = item.data('id');

        $.post('admin_pedidos.php', { action: 'marcar_atendido', id: id }, function(res) {
            if (res.status === 'success') {
                item.fadeOut(500, function() {
                    location.reload(); 
                });
            } else {
                Swal.fire('Erro!', 'Não foi possível atualizar o pedido.', 'error');
            }
        }, 'json');
    });

    // Ações de gerenciamento (apagar)
    $('.manage-btn').on('click', function() {
        const action = $(this).data('action');
        Swal.fire({
            title: 'Você tem certeza?', text: "Esta ação não pode ser desfeita!", icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Sim, executar!', cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('admin_pedidos.php', { action: action }, function(res) {
                    if (res.status === 'success') {
                        location.reload();
                    } else {
                        Swal.fire('Erro!', 'Não foi possível executar a ação.', 'error');
                    }
                }, 'json');
            }
        });
    });
});
</script>