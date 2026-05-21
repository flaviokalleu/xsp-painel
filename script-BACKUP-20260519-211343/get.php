<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// ===================================================================
// AQUI ESTÁ A CORREÇÃO FINAL USANDO O CAMINHO ABSOLUTO DO SEU SERVIDOR
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
// ===================================================================

$conexao = conectar_bd();

if (!$conexao) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro fatal: não foi possível conectar ao banco de dados."]);
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

        // Atualiza canais e filmes (assumindo que estão na tabela 'streams')
        $sql_streams = "UPDATE streams SET stream_source = REPLACE(stream_source, ?, ?)";
        $stmt_streams = $conexao->prepare($sql_streams);
        $stmt_streams->execute([$texto_procurar, $texto_substituir]);
        $total_afetado += $stmt_streams->rowCount();

        // Atualiza filmes que podem estar em uma tabela separada 'movies'
        $sql_movies = "UPDATE movies SET stream_source = REPLACE(stream_source, ?, ?)";
        $stmt_movies = $conexao->prepare($sql_movies);
        $stmt_movies->execute([$texto_procurar, $texto_substituir]);
        $total_afetado += $stmt_movies->rowCount();
        
        // Atualiza séries que podem estar em uma tabela separada 'series_episodes'
        $sql_series = "UPDATE series_episodes SET stream_source = REPLACE(stream_source, ?, ?)";
        $stmt_series = $conexao->prepare($sql_series);
        $stmt_series->execute([$texto_procurar, $texto_substituir]);
        $total_afetado += $stmt_series->rowCount();

        $conexao->commit();

        if ($total_afetado > 0) {
            echo json_encode(["status" => "success", "message" => "Atualização concluída! " . $total_afetado . " links foram modificados."]);
        } else {
            // Importante: retorna 'success' aqui para não mostrar erro, apenas um aviso.
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