<?php
// ARQUIVO: /gerenciador/ajax/serie_excluir.php (VERSÃO CORRIGIDA FINAL)

header('Content-Type: application/json');
require_once('../../api/controles/db.php');
$pdo = conectar_bd();

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Erro: Não foi possível conectar ao banco de dados.']);
    exit;
}

$serie_id = $_POST['id'] ?? null;

if (empty($serie_id)) {
    echo json_encode(['success' => false, 'message' => 'Erro: Nenhum ID de série foi fornecido.']);
    exit;
}

try {
    // Para garantir que tudo seja salvo, iniciamos uma transação.
    $pdo->beginTransaction();

    // Passo 1: Excluir os episódios associados a esta série da tabela 'series_episodes'.
    $stmtEpisodes = $pdo->prepare("DELETE FROM series_episodes WHERE series_id = :series_id");
    $stmtEpisodes->execute([':series_id' => $serie_id]);

    // Passo 2: Excluir a série principal da tabela 'series'.
    $stmtSerie = $pdo->prepare("DELETE FROM series WHERE id = :id");
    $stmtSerie->execute([':id' => $serie_id]);

    // Se ambos os comandos executaram sem erro, salva as alterações permanentemente.
    $pdo->commit();

    // Verificamos se a linha da SÉRIE foi de fato apagada.
    if ($stmtSerie->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Série e seus episódios foram excluídos com sucesso!']);
    } else {
        // Se a série não foi encontrada, a transação é desfeita, mas informamos o usuário.
        echo json_encode(['success' => false, 'message' => 'Nenhuma série encontrada com o ID fornecido.']);
    }

} catch (PDOException $e) {
    // Se qualquer um dos comandos DELETE falhar, desfazemos TODAS as alterações.
    $pdo->rollBack();
    error_log('Erro ao excluir série: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocorreu um erro no banco de dados. Detalhe: ' . $e->getMessage()]);
}
?>