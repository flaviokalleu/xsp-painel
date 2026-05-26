<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("menu.php"); // Inclui o seu menu e o cabeçalho da página
require_once("./api/controles/db.php"); 
$conexao = conectar_bd(); 

// Lógica para enviar um novo pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_pedido'])) {
    $tipo = $_POST['tipo'] ?? 'filme';
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $admin_id = $_SESSION['admin_id'];
    $nome_admin = $_SESSION['username'];

    if (!empty($titulo)) {
        // ======================================================================
        //      CORREÇÃO APLICADA AQUI
        //      Adicionamos a coluna 'status' e o valor 'pendente' no INSERT
        // ======================================================================
        $stmt = $conexao->prepare("INSERT INTO pedidos_vod (admin_id, nome_admin, tipo, titulo, descricao, status) VALUES (?, ?, ?, ?, ?, 'pendente')");
        $stmt->execute([$admin_id, $nome_admin, $tipo, $titulo, $descricao]);
    }
    
    // Redireciona para evitar reenvio do formulário
    echo '<script>window.location.href="pedidos.php";</script>';
    exit();
}

// Busca os pedidos do revendedor logado
$admin_id_logado = $_SESSION['admin_id'];
$pedidos_pendentes = $conexao->prepare("SELECT * FROM pedidos_vod WHERE admin_id = ? AND status = 'pendente' ORDER BY data_pedido DESC");
$pedidos_pendentes->execute([$admin_id_logado]);

$pedidos_atendidos = $conexao->prepare("SELECT * FROM pedidos_vod WHERE admin_id = ? AND status = 'atendido' ORDER BY data_pedido DESC");
$pedidos_atendidos->execute([$admin_id_logado]);
?>

<h4 class="align-items-center d-flex justify-content-between mb-4 text-muted text-uppercase">
    Solicitar Filmes ou Séries
</h4>

<div class="card mb-4">
    <div class="card-header fw-bold" style="color: var(--text-primary);">Novo Formulário</div>
    <div class="card-body">
        <p>Utilize o formulário abaixo para solicitar novos filmes ou séries.</p>
        <form method="POST" action="pedidos.php">
            <input type="hidden" name="enviar_pedido" value="1">
            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo:</label>
                <select id="tipo" name="tipo" class="form-select" style="background-color: var(--bg-card); color: var(--text-primary);">
                    <option value="filme">Filme</option>
                    <option value="serie">Série</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="titulo" class="form-label">Título:</label>
                <input type="text" id="titulo" name="titulo" class="form-control" required  style="background-color: var(--bg-card); color: var(--text-primary);">
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição (opcional):</label>
                <textarea id="descricao" name="descricao" class="form-control" rows="3" placeholder="Ex: Ano do filme, temporada da série, etc." style="background-color: var(--bg-card); color: var(--text-primary);"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Enviar Pedido</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header fw-bold" style="color: var(--text-primary);"><i class="fas fa-clock"></i> Pedidos Pendentes</div>
    <div class="card-body">
        <?php if ($pedidos_pendentes->rowCount() > 0): ?>
            <ul class="list-group">
                <?php while ($pedido = $pedidos_pendentes->fetch()): ?>
                    <li class="list-group-item" style="background-color: var(--bg-card); color: var(--text-primary);">
                        <strong><?php echo htmlspecialchars($pedido['titulo']); ?></strong> (<?php echo ucfirst($pedido['tipo']); ?>)
                        <br><small class="text-muted"><?php echo htmlspecialchars($pedido['descricao']); ?></small>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>Nenhum pedido pendente encontrado.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header fw-bold" style="color: var(--text-primary);"><i class="fas fa-check-circle"></i> Pedidos Atendidos</div>
    <div class="card-body">
        <?php if ($pedidos_atendidos->rowCount() > 0): ?>
            <ul class="list-group">
                <?php while ($pedido = $pedidos_atendidos->fetch()): ?>
                    <li class="list-group-item text-muted" style="background-color: var(--bg-card); text-decoration: line-through;">
                        <strong><?php echo htmlspecialchars($pedido['titulo']); ?></strong> (<?php echo ucfirst($pedido['tipo']); ?>)
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>Nenhum pedido atendido encontrado.</p>
        <?php endif; ?>
    </div>
</div>