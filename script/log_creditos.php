<?php
/*
================================================================================
|             PÁGINA DE HISTÓRICO DE CRÉDITOS (log_creditos.php)                |
|             Modificado: Verifica o tipo de usuário (admin/revenda)           |
|             em vez de depender do ID para exibir os logs corretos.           |
================================================================================
*/

if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Verificação de sessão real do painel
if (empty($_SESSION['admin_id'])) {
    // Se não estiver logado, pode redirecionar ou mostrar erro
    die("Acesso negado. Por favor, faça login.");
}

require_once(__DIR__ . '/api/controles/db.php');

// Pega o ID do usuário logado a partir da sessão real
$logged_user_id = (int)$_SESSION['admin_id'];

$pdo = conectar_bd();

// Busca os dados completos do usuário logado para verificar se é admin
$stmt_user = $pdo->prepare("SELECT * FROM admin WHERE id = :id");
$stmt_user->execute([':id' => $logged_user_id]);
$logged_user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$logged_user_data) {
    die("Usuário logado não encontrado no banco de dados.");
}

// A coluna 'admin' com valor 1 define um administrador
$is_admin = ($logged_user_data['admin'] == 1);

$page_title = "Histórico Geral de Créditos";
$log_entries = [];

/**
 * Busca recursivamente todos os IDs de sub-revendedores de um dono.
 */
function getAllSubResellerIds(PDO $pdo, int $owner_id): array {
    $sql = "SELECT id FROM (SELECT * FROM admin ORDER BY owner_id, id) users_sorted, (SELECT @pv := :owner_id) initialisation WHERE find_in_set(owner_id, @pv) AND length(@pv := concat(@pv, ',', id))";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
    $stmt->execute();
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $ids[] = $owner_id;
    return $ids;
}

/**
 * Busca o histórico de transações de crédito.
 */
function getCreditsLog(PDO $pdo, ?array $reseller_ids): array {
    $sql = "
        SELECT 
            cl.id, cl.amount, cl.date, cl.reason,
            target.user AS target_username,
            owner.user AS owner_username
        FROM 
            credits_log cl
        LEFT JOIN 
            admin AS target ON cl.target_id = target.id
        LEFT JOIN 
            admin AS owner ON cl.admin_id = owner.id
    ";

    if ($reseller_ids !== null && !empty($reseller_ids)) {
        $in_clause = implode(',', array_fill(0, count($reseller_ids), '?'));
        $sql .= " WHERE cl.admin_id IN ($in_clause) OR cl.target_id IN ($in_clause)";
    }

    $sql .= " ORDER BY cl.id DESC";
    
    $stmt = $pdo->prepare($sql);

    if ($reseller_ids !== null && !empty($reseller_ids)) {
        $stmt->execute(array_merge($reseller_ids, $reseller_ids));
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lógica principal para determinar o que mostrar
if ($is_admin) { // Se for o Admin principal
    $log_entries = getCreditsLog($pdo, null); // `null` busca todos os logs.
} else { // Se for um Revendedor
    $reseller_tree_ids = getAllSubResellerIds($pdo, $logged_user_id);
    $log_entries = getCreditsLog($pdo, $reseller_tree_ids);
    $page_title = "Histórico de Créditos da Revenda";
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Créditos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
</head>
<body class="p-3 p-md-4">
    <div class="container">
        <div class="card">
            <div class="card-header bg-secondary text-white text-center">
                <h2 class="mb-0"><i class="fas fa-history"></i> <?php echo $page_title; ?></h2>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <a href="gerenciar_revendedores.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Voltar para Gerenciamento
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Origem</th>
                                <th>Destino</th>
                                <th class="text-center">Quantidade</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($log_entries)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Nenhum registro encontrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($log_entries as $entry): ?>
                                    <tr>
                                        <td><?php echo $entry['id']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', $entry['date']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['owner_username'] ?? 'Sistema'); ?></td>
                                        <td><?php echo htmlspecialchars($entry['target_username'] ?? 'N/A'); ?></td>
                                        <td class="text-center">
                                            <?php if ($entry['amount'] > 0): ?>
                                                <span class="badge bg-success fs-6">+<?php echo $entry['amount']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger fs-6"><?php echo $entry['amount']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($entry['reason'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
