<?php
// api.php
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/includes/db.php');
require_once(__DIR__ . '/includes/TMDB.php');

$action = $_GET['action'] ?? null;
$type = $_GET['type'] ?? 'movie'; // 'movie' ou 'tv'

// Mapeamento das tabelas e colunas (AJUSTE CONFORME SUAS TABELAS)
$config = [
    'movie' => [
        'table' => 'streams',
        'cols' => ['name', 'plot', 'stream_icon', 'backdrop_path', 'releaseDate', 'rating', 'rating_5based', 'year', 'genre', 'director', 'actors', 'duration', 'youtube_trailer', 'tmdb_id']
    ],
    'tv' => [
        'table' => 'series',
        'cols' => ['name', 'plot', 'cover', 'backdrop_path', 'releaseDate', 'rating', 'rating_5based', 'year', 'genre', 'director', 'cast', 'youtube_trailer', 'tmdb_id']
    ]
];

$pdo = conectar_banco();

switch ($action) {
    case 'get_items_to_update':
        $table = $config[$type]['table'];
        // Busca itens que nunca foram checados (tmdb_id é NULL)
        $stmt = $pdo->query("SELECT id, name FROM {$table} WHERE tmdb_id IS NULL");
        echo json_encode($stmt->fetchAll());
        break;

    case 'update_item':
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? null;
        if (!$id || !$name) {
            echo json_encode(['success' => false, 'message' => 'ID ou Nome não fornecido.']);
            exit;
        }

        $table = $config[$type]['table'];
        $match = TMDB::findBestMatch($type, $name);

        if (!$match || !isset($match['id'])) {
            // Marca como -1 para não buscar novamente
            $stmt = $pdo->prepare("UPDATE {$table} SET tmdb_id = -1 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => false, 'message' => 'Não encontrado no TMDB.']);
            exit;
        }

        $tmdbId = $match['id'];
        $details = TMDB::getDetails($type, $tmdbId);

        if (!$details) {
            echo json_encode(['success' => false, 'message' => 'Falha ao obter detalhes do TMDB.']);
            exit;
        }
        
        $details['tmdb_id'] = $tmdbId;

        // Monta a query de UPDATE dinamicamente
        $setClauses = [];
        $params = [];
        foreach ($config[$type]['cols'] as $col) {
            // Mapeamento especial de colunas se os nomes forem diferentes
            $detailKey = ($type === 'movie' && $col === 'stream_icon') ? 'stream_icon' : 
                         (($type === 'tv' && $col === 'cover') ? 'cover' : $col);
            
            if (isset($details[$detailKey])) {
                $setClauses[] = "{$col} = :{$col}";
                $params[":{$col}"] = $details[$detailKey];
            }
        }
        $params[':id'] = $id;

        if (empty($setClauses)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum detalhe para atualizar.']);
            exit;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setClauses) . " WHERE id = :id";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Atualizado com sucesso.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro no Banco: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Ação inválida.']);
        break;
}
?>