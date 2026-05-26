<?php
/*
================================================================================
|                  LÓGICA DO SERVIDOR (api_revendedores.php)                   |
|             Versão Final: Lógica de permissão baseada no tipo de usuário.    |
================================================================================
*/

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Verificação de sessão real do painel
if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado. Faça login novamente.']);
    exit();
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
    echo json_encode(['success' => false, 'message' => 'Usuário logado não encontrado.']);
    exit();
}

// A coluna 'admin' com valor 1 define um administrador
$is_admin = ($logged_user_data['admin'] == 1);

// --- FUNÇÕES DE LÓGICA ---

function getResellersByOwner(PDO $pdo, int $owner_id, bool $is_admin_view): array {
    $sql = "
        SELECT 
            r.id, r.user, r.creditos, r.owner_id,
            o.user as owner_name 
        FROM 
            admin r
        LEFT JOIN 
            admin o ON r.owner_id = o.id
    ";

    if ($is_admin_view) {
        $sql .= " WHERE r.id != 1"; // Admin vê todos, exceto ele mesmo.
        $stmt = $pdo->prepare($sql);
    } else {
        $sql .= " WHERE r.owner_id = :owner_id AND r.id != 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function transferCredits(PDO $pdo, int $admin_id, int $target_id, int $amount, string $reason): array {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = :id");
    $stmt->execute(['id' => $admin_id]);
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt->execute(['id' => $target_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin_user || !$target_user) {
        return ['success' => false, 'message' => 'Usuário não encontrado.'];
    }

    $is_main_admin = ($admin_user['admin'] == 1);

    if (!$is_main_admin && $admin_user['creditos'] < $amount) {
        return ['success' => false, 'message' => 'Créditos insuficientes.'];
    }

    try {
        $pdo->beginTransaction();

        if (!$is_main_admin) {
            $sql_admin = "UPDATE admin SET creditos = creditos - :amount WHERE id = :admin_id";
            $stmt_admin = $pdo->prepare($sql_admin);
            $stmt_admin->execute(['amount' => $amount, 'admin_id' => $admin_id]);
        }
        
        $sql_target = "UPDATE admin SET creditos = creditos + :amount WHERE id = :target_id";
        $stmt_target = $pdo->prepare($sql_target);
        $stmt_target->execute(['amount' => $amount, 'target_id' => $target_id]);

        $sql_log = "INSERT INTO credits_log (target_id, admin_id, amount, date, reason) VALUES (:target_id, :admin_id, :amount, :date, :reason)";
        $stmt_log = $pdo->prepare($sql_log);
        $stmt_log->execute([
            'target_id' => $target_id,
            'admin_id' => $admin_id,
            'amount' => $amount,
            'date' => time(),
            'reason' => $reason
        ]);

        $pdo->commit();
        return ['success' => true];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Erro no banco de dados. A transação foi revertida.'];
    }
}

// --- CONTROLE DE AÇÕES (ROTEAMENTO) ---

$action = $_GET['action'] ?? '';

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Não foi possível conectar ao banco de dados.']);
    exit();
}

switch ($action) {
    case 'get_resellers':
        $resellers = getResellersByOwner($pdo, $logged_user_id, $is_admin);
        echo json_encode(['success' => true, 'data' => $resellers]);
        break;

    case 'change_credits':
        $reseller_id = (int)($_POST['reseller_id'] ?? 0);
        $credits = (int)($_POST['credits'] ?? 0);
        $reason = htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES, 'UTF-8');
        
        if ($reseller_id > 0 && $credits != 0) {
            $result = transferCredits($pdo, $logged_user_id, $reseller_id, $credits, $reason);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        }
        break;

    case 'log_renewal':
        $reseller_id = (int)($_POST['reseller_id'] ?? 0);
        $cost = (int)($_POST['cost'] ?? 0);
        $client_info = htmlspecialchars($_POST['client_info'] ?? '', ENT_QUOTES, 'UTF-8');

        if ($reseller_id > 0 && $cost > 0) {
            $reason = "Renovação cliente: {$client_info} (-{$cost} créditos)";
            
            $sql_log = "INSERT INTO credits_log (target_id, admin_id, amount, date, reason) VALUES (:target_id, :admin_id, :amount, :date, :reason)";
            $stmt_log = $pdo->prepare($sql_log);
            
            $success = $stmt_log->execute([
                'target_id' => $reseller_id,
                'admin_id' => $reseller_id,
                'amount' => -$cost,
                'date' => time(),
                'reason' => $reason
            ]);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Log de renovação registrado.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Falha ao registrar o log.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos para registrar o log.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
        break;
}
?>
