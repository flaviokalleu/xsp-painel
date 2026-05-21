<?php
// ======================================================================
//   PLAYER API v10.2 - Versão Final de Correção de Atividade
// ======================================================================

// Se precisar de depurar no futuro, pode remover o comentário das 3 linhas abaixo
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

$username = $_GET['username'] ?? $_POST['username'] ?? null;
$password = $_GET['password'] ?? $_POST['password'] ?? null;
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$category_id_req = ($_GET['category_id'] ?? null) === '*' ? null : ($_GET['category_id'] ?? null);

// ==========================================================
// [MUDANÇA CRÍTICA AQUI] Captura o ID do Stream de todas as variáveis comuns
$stream_id = $_GET['stream_id'] ?? $_GET['id'] ?? $_GET['stream'] ?? null; 
// ==========================================================

$series_id = $_GET['series_id'] ?? null;

if (empty($action) && (!$username || !$password)) {
    echo json_encode(['user_info' => ['auth' => 0, 'message' => 'Usuário e senha são necessários.']]);
    exit;
}

try {
    $conexao = conectar_bd();
    $stmt_auth = $conexao->prepare("SELECT * FROM clientes WHERE usuario = :username AND senha = :password");
    $stmt_auth->bindValue(':username', $username);
    $stmt_auth->bindValue(':password', $password);
    $stmt_auth->execute();
    $cliente_info = $stmt_auth->fetch(PDO::FETCH_ASSOC);

    if (!$cliente_info) {
        echo json_encode(['user_info' => ['auth' => 0, 'status' => 'Invalid Credentials']]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erro de DB na autenticação: " . $e->getMessage());
    echo json_encode(['user_info' => ['auth' => 0, 'message' => 'Erro interno do servidor.']]);
    exit;
}

$allowed_category_ids = null;
if (!empty($cliente_info['bouquet_id'])) {
    $stmt_bouquet = $conexao->prepare("SELECT category_id FROM bouquet_items WHERE bouquet_id = ?");
    $stmt_bouquet->execute([$cliente_info['bouquet_id']]);
    $allowed_category_ids = $stmt_bouquet->fetchAll(PDO::FETCH_COLUMN);
    if (empty($allowed_category_ids)) { $allowed_category_ids = [0]; } 
}

switch ($action) {
    case '': // Ação de Login
        $exp_date = empty($cliente_info['Vencimento']) ? 0 : strtotime($cliente_info['Vencimento']);
        $status = ($exp_date !== 0 && $exp_date >= time()) ? "Active" : "Expired";
        $auth = ($status === "Active") ? 1 : 0;
        
        $response = [
            'user_info' => [
                'username' => (string)$cliente_info['usuario'],
                'password' => (string)$cliente_info['senha'],
                'message' => 'Bem Vindo!',
                'auth' => (int)$auth,
                'status' => (string)$status,
                'exp_date' => (string)$exp_date,
                'is_trial' => (string)($cliente_info['is_trial'] ?? '0'),
                'active_cons' => 0,
                'created_at' => (string)strtotime($cliente_info['Criado_em']),
                'max_connections' => (string)($cliente_info['conexoes'] ?? '1'),
                'allowed_output_formats' => ['m3u8', 'ts', 'mp4'],
            ],
            'server_info' => [
                'url' => $_SERVER['HTTP_HOST'], 'port' => $_SERVER['SERVER_PORT'], 'https_port' => "443",
                'server_protocol' => $_SERVER['REQUEST_SCHEME'], 'rtmp_port' => '8880',
                'timestamp_now' => time(), 'time_now' => date('Y-m-d H:i:s'),
                'timezone' => date_default_timezone_get()
            ]
        ];
        echo json_encode($response);
        break;

    case 'get_live_categories':
    case 'get_vod_categories':
    case 'get_series_categories':
        $typeMap = ['get_live_categories' => 'live', 'get_vod_categories' => 'movie', 'get_series_categories' => 'series'];
        $type = $typeMap[$action];

        // Confirme se sua tabela de categorias se chama 'categoria'
        $query = "SELECT id as category_id, nome as category_name, parent_id FROM categoria WHERE type = ?";
        $params = [$type];

        if ($cliente_info['adulto'] == 0) { $query .= " AND is_adult = 0"; }

        if ($allowed_category_ids !== null) {
            $placeholders = implode(',', array_fill(0, count($allowed_category_ids), '?'));
            $query .= " AND id IN ($placeholders)";
            $params = array_merge($params, $allowed_category_ids);
        }
        $query .= " ORDER BY position ASC";
        
        $stmt = $conexao->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($results)) { $results[] = ["category_id" => "*", "category_name" => "Sem categorias", "parent_id" => 0]; }
        echo json_encode($results);
        break;

    case 'get_live_streams':
    case 'get_vod_streams':
    case 'get_series':
        $typeMap = ['get_live_streams' => 'live', 'get_vod_streams' => 'movie', 'get_series' => 'series'];
        $tableMap = ['get_live_streams' => 'streams', 'get_vod_streams' => 'streams', 'get_series' => 'series'];
        $stream_type = $typeMap[$action];
        $table = $tableMap[$action];

        $query = "SELECT t1.* FROM `{$table}` AS t1";
        $params = [];
        $conditions = [];

        if ($table === 'streams') {
            $conditions[] = "t1.stream_type = ?";
            $params[] = $stream_type;
        }
        
        if ($cliente_info['adulto'] == 0) {
            $query .= " INNER JOIN `categoria` AS t2 ON t1.category_id = t2.id";
            $conditions[] = "t2.is_adult = 0";
        }
        
        if ($allowed_category_ids !== null) {
            $in_placeholders = implode(',', array_fill(0, count($allowed_category_ids), '?'));
            $conditions[] = "t1.category_id IN ($in_placeholders)";
            $params = array_merge($params, $allowed_category_ids);
        }
        
        if ($category_id_req) {
            $conditions[] = "t1.category_id = ?";
            $params[] = $category_id_req;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $query .= " ORDER BY t1.name ASC";
        
        $stmt = $conexao->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted_results = [];
        foreach ($results as $num => $row) {
            if ($action == 'get_live_streams') {
                $formatted_results[] = [
                    "num" => $num + 1, "name" => $row["name"] ?? '', "stream_type" => "live",
                    "stream_id" => (int)$row["id"], "stream_icon" => $row["stream_icon"] ?? '',
                    "epg_channel_id" => $row["epg_channel_id"], "added" => (string)($row["added"] ?? ''),
                    "category_id" => (string)$row["category_id"], "tv_archive" => 0, "tv_archive_duration" => "0"
                ];
            } elseif ($action == 'get_vod_streams') {
                $rating = $row["rating"] ?? '0';
                $formatted_results[] = [
                    "num" => $num + 1, "name" => $row["name"] ?? '', "stream_type" => "movie",
                    "stream_id" => (int)$row["id"], "stream_icon" => $row["stream_icon"] ?? '',
                    "rating" => (string)$rating, "rating_5based" => round(((float)$rating / 2), 1),
                    "added" => (string)($row["added"] ?? ''), "category_id" => (string)$row["category_id"],
                    "container_extension" => $row["container_extension"] ?? "mp4"
                ];
            } elseif ($action == 'get_series') {
                 $rating = $row["rating"] ?? '0';
                $formatted_results[] = [
                    "num" => $num + 1, "name" => $row["name"] ?? '', "series_id" => (int)$row["id"],
                    "cover" => $row["cover"] ?? '', "plot" => $row["plot"] ?? '', "cast" => $row["cast"] ?? '',
                    "director" => $row["director"] ?? '', "genre" => $row["genre"] ?? '',
                    "releaseDate" => $row["release_date"] ?? '', "last_modified" => (string)($row["last_modified"] ?? ''),
                    "rating" => (string)$rating, "rating_5based" => round(((float)$rating / 2), 1),
                    "youtube_trailer" => $row["youtube_trailer"] ?? "", "episode_run_time" => $row["episode_run_time"] ?? "0",
                    "category_id" => (string)$row["category_id"]
                ];
            }
        }
        echo json_encode($formatted_results);
        break;

    case 'get_short_epg':
        if (!$stream_id) { echo json_encode(['epg_listings' => []]); exit; }
        
        $stmt_epg_id = $conexao->prepare("SELECT epg_channel_id FROM streams WHERE id = ?");
        $stmt_epg_id->execute([$stream_id]);
        $epg_channel_id = $stmt_epg_id->fetchColumn();

        if (!$epg_channel_id) { echo json_encode(['epg_listings' => []]); exit; }

        $stmt_epg = $conexao->prepare("SELECT title, start, `end` FROM epg WHERE channel_id = ? AND `end` > NOW() ORDER BY start ASC LIMIT 50");
        $stmt_epg->execute([$epg_channel_id]);
        $epg_listings = $stmt_epg->fetchAll(PDO::FETCH_ASSOC);

        $formatted_epg = [];
        foreach ($epg_listings as $listing) {
            $formatted_epg[] = [
                'id' => uniqid(), 'title' => base64_encode($listing['title']), 'start' => $listing['start'], 'end' => $listing['end'],
                'description' => base64_encode(''), 'start_timestamp' => (string)strtotime($listing['start']),
                'stop_timestamp' => (string)strtotime($listing['end'])
            ];
        }
        echo json_encode(['epg_listings' => $formatted_epg]);
        break;
        
    case 'get_series_info':
         if (!$series_id) { echo json_encode([]); exit; }
         $stmt_episodes = $conexao->prepare("SELECT * FROM series_episodes WHERE series_id = ? ORDER BY season, episode_num ASC");
         $stmt_episodes->execute([$series_id]);
         $episodes_data = $stmt_episodes->fetchAll(PDO::FETCH_ASSOC);
         
         $episodes_by_season = [];
         foreach ($episodes_data as $ep) {
            $season = (int)$ep['season'];
            if (!isset($episodes_by_season[$season])) {
                $episodes_by_season[$season] = [];
            }
            $episodes_by_season[$season][] = [
                "id" => (string)$ep["id"],
                "episode_num" => (string)$ep["episode_num"],
                "title" => (string)$ep["title"],
                "container_extension" => "mp4", // Ou outra extensão
                "info" => [
                    "movie_image" => $ep["movie_image"] ?? '',
                    "plot" => $ep["plot"] ?? '',
                    "duration" => $ep["duration"] ?? '',
                    "rating" => $ep["rating"] ?? '0'
                ],
                "added" => (string)strtotime($ep["added"] ?? 'now'),
            ];
         }

        // Informação da série
         $stmt_series = $conexao->prepare("SELECT * FROM series WHERE id = ?");
         $stmt_series->execute([$series_id]);
         $series_info = $stmt_series->fetch(PDO::FETCH_ASSOC);

         $response = [
             "seasons" => array_values($episodes_by_season), // A API espera um array, não um objeto
             "info" => [
                "name" => $series_info["name"] ?? '', "cover" => $series_info["cover"] ?? '', "plot" => $series_info["plot"] ?? '',
                "cast" => $series_info["cast"] ?? '', "director" => $series_info["director"] ?? '',
                "genre" => $series_info["genre"] ?? '', "releaseDate" => $series_info["release_date"] ?? '',
                "last_modified" => (string)strtotime($series_info["last_modified"] ?? 'now'),
                "rating" => (string)($series_info["rating"] ?? '0'), "rating_5based" => round(((float)($series_info["rating"] ?? 0) / 2), 1),
                "youtube_trailer" => $series_info["youtube_trailer"] ?? ''
             ],
             "episodes" => $episodes_by_season,
         ];
        
         echo json_encode($response);
         break;

    default:
        // ==========================================================
        //  [CORREÇÃO FINAL: BLOCO DE ATUALIZAÇÃO NO CASO 'DEFAULT']
        //  Isso captura qualquer ação não reconhecida, mas que contenha o stream_id.
        // ==========================================================
        if ($stream_id && $username) {
            $stmt_update = $conexao->prepare("
                UPDATE conexoes 
                SET canal_atual = :stream_id, ultima_atividade = NOW()
                WHERE usuario = :username 
                ORDER BY ultima_atividade DESC 
                LIMIT 1
            ");
            $stmt_update->bindValue(':stream_id', $stream_id);
            $stmt_update->bindValue(':username', $username);
            $stmt_update->execute();
        }
        // Retorna a resposta padrão da API para a ação desconhecida (geralmente [])
        echo json_encode([]);
        break;
}
?>