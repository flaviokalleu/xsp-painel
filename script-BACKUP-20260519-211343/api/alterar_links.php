<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Caminho absoluto e correto para o arquivo de conexão
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');

$conexao = conectar_bd();

if (!$conexao) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro fatal: não foi possível conectar ao banco de dados. Verifique as credenciais no arquivo db.php."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        $texto_procurar = $data['link_m3u'] ?? '';
        $texto_substituir = $data['nova_url'] ?? '';

        if (empty($texto_procurar)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "O campo 'Link da M3U' (Texto a Procurar) não pode estar vazio."]);
            exit();
        }

        $conexao->beginTransaction();
        $total_afetado = 0;

        // ===== CORREÇÃO 1: A coluna foi alterada de 'stream_source' para 'link' para bater com seu banco de dados =====
        $sql_streams = "UPDATE streams SET link = REPLACE(link, ?, ?)";
        $stmt_streams = $conexao->prepare($sql_streams);
        $stmt_streams->execute([$texto_procurar, $texto_substituir]);
        $total_afetado += $stmt_streams->rowCount();

        // ===== CORREÇÃO 2: A query para a tabela 'movies' foi REMOVIDA, pois ela não existe no seu banco. =====

        // ===== CORREÇÃO 3: A coluna em 'series_episodes' também foi assumida como 'link'. =====
        // (Nota: Se apenas as séries não atualizarem, verifique o nome desta coluna na tabela 'series_episodes' no phpMyAdmin)
        $sql_series = "UPDATE series_episodes SET link = REPLACE(link, ?, ?)";
        $stmt_series = $conexao->prepare($sql_series);
        $stmt_series->execute([$texto_procurar, $texto_substituir]);
        $total_afetado += $stmt_series->rowCount();

        $conexao->commit();

        if ($total_afetado > 0) {
            echo json_encode(["status" => "success", "message" => "Atualização concluída! " . $total_afetado . " links foram modificados."]);
        } else {
            echo json_encode(["status" => "success", "message" => "Operação concluída, mas nenhum link correspondente foi encontrado para alterar."]);
        }

    } catch (PDOException $e) {
        $conexao->rollBack();
        error_log('Erro na atualização em massa: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Erro de banco de dados: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método de requisição inválido."]);
}
?>