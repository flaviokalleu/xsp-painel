<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

// Resposta padrão
$response = ['status' => 'pendente'];

if (!isset($_SESSION['client_loggedin'])) {
    // Se não estiver logado, não faz nada
    echo json_encode(['status' => 'erro', 'message' => 'Não autenticado.']);
    exit();
}

require_once('../api/controles/db.php');
$conexao = conectar_bd();

$client_id = $_SESSION['client_id'];
$vencimento_original = $_POST['vencimento_original'] ?? null;

if ($conexao && $client_id && $vencimento_original) {
    try {
        $stmt = $conexao->prepare("SELECT Vencimento FROM clientes WHERE id = ?");
        $stmt->execute([$client_id]);
        $novo_vencimento = $stmt->fetchColumn();

        // Compara o timestamp do novo vencimento com o original
        // Se o novo for maior, significa que a renovação ocorreu.
        if (strtotime($novo_vencimento) > strtotime($vencimento_original)) {
            $response['status'] = 'aprovado';
        }

    } catch (Exception $e) {
        // Em caso de erro, não faz nada e continua como pendente
    }
}

echo json_encode($response);
?>