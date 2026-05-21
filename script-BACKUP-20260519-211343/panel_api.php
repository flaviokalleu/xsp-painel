<?php
// Este é o seu arquivo original. A única alteração é o bloco de código "edit_user" adicionado no meio.
header('Content-Type: text/plain; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');

date_default_timezone_set('America/Sao_Paulo');
$username = isset($_GET['username']) ? $_GET['username'] : null;
$password = isset($_GET['password']) ? $_GET['password'] : null;

if (!$username || !$password) {
    http_response_code(401);
    $errorResponse['user_info']['auth'] = 0;
    $errorResponse['user_info']['msg'] = "username e password necessario!";
    echo json_encode($errorResponse);
    exit();
}

$conexao = conectar_bd();
$query = "SELECT * FROM clientes WHERE usuario = :username AND senha = :password";
$statement = $conexao->prepare($query);
$statement->bindValue(':username', $username);
$statement->bindValue(':password', $password);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);

// ===================================================================
// >> BLOCO DE CÓDIGO ADICIONADO PARA O WEBHOOK FUNCIONAR <<
// ===================================================================
if (isset($_GET['action']) && $_GET['action'] == 'edit_user') {
    $admin_query = $conexao->prepare("SELECT * FROM admin WHERE user = :username AND pass = :password");
    $admin_query->execute([':username' => $username, ':password' => $password]);
    
    if ($admin_query->fetch()) {
        $user_id_to_edit = $_GET['user_id'] ?? 0;
        $new_exp_timestamp = $_GET['exp_date'] ?? 0;
        if ($user_id_to_edit > 0 && $new_exp_timestamp > 0) {
            $new_exp_date = date("Y-m-d 23:59:59", $new_exp_timestamp);
            $update_stmt = $conexao->prepare("UPDATE clientes SET Vencimento = :vencimento WHERE id = :id");
            if ($update_stmt->execute([':vencimento' => $new_exp_date, ':id' => $user_id_to_edit])) {
                echo json_encode(['user_info' => ['auth' => 1, 'status' => 'Active'], 'message' => 'User updated successfully.']);
                exit();
            }
        }
    }
    echo json_encode(['result' => false, 'message' => 'Failed to update user.']);
    exit();
}
// >> FIM DO BLOCO ADICIONADO <<
// ===================================================================


if (!$result) {
    http_response_code(401);
    echo json_encode([["auth" => 0]]);
    exit();
}

// O resto do seu código original para gerar a playlist continua aqui...
$exp_date = strtotime($result['Vencimento']);
$created_at = strtotime($result['Criado_em']);
$status = ($exp_date < time()) ? "Inactive" : "Active";
$auth = ($status === "Active") ? "1" : "0";
// ... (e assim por diante, mantenha todo o resto do seu arquivo original)
?>