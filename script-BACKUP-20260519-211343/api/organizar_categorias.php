<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once('./controles/db.php');
$conexao = conectar_bd();

if (!$conexao) {
    echo json_encode(["status" => "error", "message" => "Erro ao conectar com o banco de dados."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type']) && isset($_POST['order'])) {
    $type = $_POST['type'];
    $order = $_POST['order'];

    if (!is_array($order) || empty($order)) {
        echo json_encode(["status" => "error", "message" => "Ordem inválida recebida."]);
        exit();
    }
    
    // Corrigindo o tipo de conteúdo para 'live' se for 'streams'
    if ($type === 'streams') {
        $type = 'live';
    }
    
    try {
        $conexao->beginTransaction();
        
        $sql = "UPDATE categoria SET position = ? WHERE id = ? AND type = ?";
        $stmt = $conexao->prepare($sql);
        
        foreach ($order as $index => $id) {
            $position = $index + 1;
            $stmt->execute([$position, $id, $type]);
        }
        
        $conexao->commit();
        echo json_encode(["status" => "success", "message" => "Ordem das categorias atualizada com sucesso!"]);
        
    } catch (PDOException $e) {
        $conexao->rollBack();
        echo json_encode(["status" => "error", "message" => "Erro ao salvar a ordem: " . $e->getMessage()]);
    }
    
} else {
    echo json_encode(["status" => "error", "message" => "Requisição inválida."]);
}
?>