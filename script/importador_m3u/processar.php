<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '2048M');
set_time_limit(0);

// =================================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =================================================================
$categories_table_name = 'categoria';
$category_name_column = 'nome';
$table_config = [
    'live' => [
        'table_name' => 'streams',
        'columns' => ['name' => 'name', 'icon' => 'stream_icon', 'cat_id' => 'category_id', 'type' => 'stream_type', 'url' => 'link']
    ],
    'movie' => [
        'table_name' => 'streams',
        'columns' => ['name' => 'name', 'icon' => 'stream_icon', 'cat_id' => 'category_id', 'type' => 'stream_type', 'url' => 'link', 'plot' => 'plot', 'rating' => 'rating', 'rating_5based' => 'rating_5based', 'genre' => 'genre', 'director' => 'director', 'actors' => 'actors', 'releaseDate' => 'releaseDate', 'backdrop' => 'backdrop_path']
    ],
    'series' => [
        'table_name' => 'series',
        'columns' => ['name' => 'name', 'icon' => 'cover', 'cat_id' => 'category_id', 'plot' => 'plot', 'rating' => 'rating', 'rating_5based' => 'rating_5based', 'genre' => 'genre', 'director' => 'director', 'cast' => 'cast', 'releaseDate' => 'releaseDate', 'backdrop' => 'backdrop_path']
    ]
];

// =================================================================
// REQUERIMENTOS (Ajuste o caminho se necessário)
// =================================================================
require_once(__DIR__ . '/../api/controles/db.php');
require_once(__DIR__ . '/TMDB.php');

// =================================================================
// CONFIGURAÇÕES GERAIS
// =================================================================
$temp_dir = __DIR__ . '/temp';
$chunk_size = 20; // Itens a processar por vez
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0775, true);
}
if (!is_writable($temp_dir)) {
    chmod($temp_dir, 0775);
}

// =================================================================
// FUNÇÕES AUXILIARES
// =================================================================

/**
 * Baixa o conteúdo de uma URL de forma robusta usando cURL.
 * @param string $url
 * @return object
 */
function fetchUrlContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    $content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($content === false || $http_code !== 200) {
        return (object)['success' => false, 'message' => "Falha ao buscar URL (HTTP: $http_code). Erro: $error"];
    }

    return (object)['success' => true, 'content' => $content];
}

/**
 * Detecta o tipo de conteúdo (live, movie, series) pela URL.
 * @param string $url
 * @return string
 */
function detectContentType($url) {
    $url_lower = strtolower($url);
    if (strpos($url_lower, '/movie/') !== false) return 'movie';
    if (strpos($url_lower, '/series/') !== false) return 'series';
    $path = parse_url($url, PHP_URL_PATH);
    if (preg_match('/\.mkv$|\.mp4$|\.avi$/', $path)) {
        if (preg_match('/s\d{1,2}e\d{1,2}/', $url_lower)) return 'series';
        return 'movie';
    }
    return 'live';
}

/**
 * Obtém o ID de uma categoria existente ou cria uma nova.
 * @param PDO $pdo
 * @param string $categoryName
 * @param string $categoryType
 * @return string
 */
function getOrCreateCategory($pdo, $categoryName, $categoryType) {
    global $categories_table_name, $category_name_column;
    $xtream_db_type = match($categoryType) { 'series' => 'series', 'live' => 'live', default => 'movie' };
    $stmt = $pdo->prepare("SELECT id FROM `{$categories_table_name}` WHERE `{$category_name_column}` = ? AND `type` = ?");
    $stmt->execute([$categoryName, $xtream_db_type]);
    $result = $stmt->fetch();
    if ($result) {
        return $result['id'];
    } else {
        $admin_id = $_SESSION['admin_id'] ?? 1;
        $stmt = $pdo->prepare("INSERT INTO `{$categories_table_name}` (`{$category_name_column}`, `type`, `admin_id`) VALUES (?, ?, ?)");
        $stmt->execute([$categoryName, $xtream_db_type, $admin_id]);
        return $pdo->lastInsertId();
    }
}

// =================================================================
// LÓGICA PRINCIPAL DO SCRIPT
// =================================================================

header('Content-Type: application/json; charset=utf-8');
$step = $_GET['step'] ?? '';

switch ($step) {
    case 'analyze':
        $source_path = $_POST['m3u_url'] ?? '';
        if (empty($source_path)) {
            echo json_encode(['success' => false, 'message' => 'URL não fornecida.']);
            exit;
        }

        $response = fetchUrlContent($source_path);
        if (!$response->success) {
            echo json_encode(['success' => false, 'message' => $response->message]);
            exit;
        }

        // Salva M3U em cache para evitar novo download no step prepare
        $cache_id = 'm3u_' . md5($source_path . time());
        $cache_file = "{$temp_dir}/{$cache_id}.m3u";
        file_put_contents($cache_file, $response->content);

        $lines = explode("\n", $response->content);
        $categories = ['live' => [], 'movie' => [], 'series' => []];
        $currentItem = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#EXTM3U') === 0) continue;

            if (strpos($line, '#EXTINF:') === 0) {
                $currentItem = ['group' => 'Sem Grupo'];
                preg_match('/group-title="([^"]+)"/', $line, $groupMatch);
                $currentItem['group'] = !empty($groupMatch[1]) ? trim($groupMatch[1]) : 'Sem Grupo';
            } else if (strpos($line, '#') !== 0 && !empty($line)) {
                if ($currentItem !== null) {
                    $item_type = detectContentType($line);
                    $categories[$item_type][] = $currentItem['group'];
                    $currentItem = null;
                }
            }
        }

        foreach ($categories as $type => &$groups) {
            $groups = array_values(array_unique($groups));
            sort($groups);
        }

        echo json_encode(['success' => true, 'categories' => $categories, 'cache_id' => $cache_id]);
        break;

    case 'prepare':
        $source_path = $_POST['m3u_url'] ?? '';
        $selected_categories = json_decode($_POST['selected_categories'] ?? '[]', true);
        $cache_id_in = $_POST['cache_id'] ?? '';

        if (empty($source_path) || empty($selected_categories)) {
            echo json_encode(['success' => false, 'message' => 'URL ou categorias não fornecidas.']);
            exit;
        }

        // Usa cache da M3U se disponível, evitando novo download
        $cached_content = null;
        if (!empty($cache_id_in)) {
            $cache_file_in = "{$temp_dir}/{$cache_id_in}.m3u";
            if (file_exists($cache_file_in)) {
                $cached_content = file_get_contents($cache_file_in);
                unlink($cache_file_in); // limpa após uso
            }
        }

        if ($cached_content === null) {
            $response = fetchUrlContent($source_path);
            if (!$response->success) {
                echo json_encode(['success' => false, 'message' => $response->message]);
                exit;
            }
            $cached_content = $response->content;
        }

        $lines = explode("\n", $cached_content);
        $playlist_data = [];
        $currentItem = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#EXTM3U') === 0) continue;

            if (strpos($line, '#EXTINF:') === 0) {
                $currentItem = ['name' => '', 'logo' => '', 'group' => 'Sem Grupo', 'url' => ''];
                preg_match('/tvg-logo="([^"]+)"/', $line, $logoMatch);
                preg_match('/group-title="([^"]+)"/', $line, $groupMatch);
                $currentItem['logo'] = $logoMatch[1] ?? '';
                $currentItem['group'] = !empty($groupMatch[1]) ? trim($groupMatch[1]) : 'Sem Grupo';
                $parts = explode(',', $line, 2);
                $currentItem['name'] = isset($parts[1]) ? trim($parts[1]) : '';
            } else if (strpos($line, '#') !== 0 && !empty($line)) {
                if ($currentItem !== null && in_array($currentItem['group'], $selected_categories)) {
                    $currentItem['url'] = $line;
                    if (!empty($currentItem['name'])) {
                        $playlist_data[] = $currentItem;
                    }
                }
                $currentItem = null;
            }
        }

        $task_id = uniqid('task_');
        $task_file = "{$temp_dir}/{$task_id}.json";
        file_put_contents($task_file, json_encode($playlist_data));
        
        echo json_encode(['success' => true, 'task_id' => $task_id, 'totalItems' => count($playlist_data)]);
        break;

    case 'process':
        $task_id = $_GET['task_id'] ?? '';
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $task_file = "{$temp_dir}/{$task_id}.json";
        $report_file = "{$temp_dir}/{$task_id}_report.txt";

        if (!file_exists($task_file)) {
            echo json_encode(['success' => false, 'message' => 'Tarefa não encontrada.']);
            exit;
        }

        $playlist = json_decode(file_get_contents($task_file), true);
        $playlist_chunk = array_slice($playlist, $offset, $chunk_size);

        $pdo = conectar_bd();
        if ($pdo === null) {
            echo json_encode(['success' => false, 'message' => 'Falha ao conectar ao banco de dados.']);
            exit;
        }
        
        $log = [];
        $added_names = ['movie' => [], 'series' => [], 'live' => []];

        foreach ($playlist_chunk as $item) {
            try {
                $content_type = detectContentType($item['url']);
                $config = $table_config[$content_type];
                $tabela = $config['table_name'];
                $col_name = $config['columns']['name'];
                $category_id = getOrCreateCategory($pdo, $item['group'], $content_type);
                
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM `{$tabela}` WHERE `{$col_name}` = ? AND `category_id` = ?");
                $stmt_check->execute([$item['name'], $category_id]);
                if ($stmt_check->fetchColumn() > 0) {
                    $log[] = ['message' => "Duplicado: {$item['name']}", 'type' => 'dim'];
                    continue;
                }
                
                $dataToInsert = ['name' => $item['name'], 'admin_id' => $_SESSION['admin_id'] ?? 1];
                $release_year = '';

                if ($content_type === 'live') {
                    $dataToInsert['icon'] = $item['logo'];
                    $dataToInsert['cat_id'] = $category_id;
                    $dataToInsert['type'] = 'live';
                    $dataToInsert['url'] = $item['url'];
                } else {
                    $log[] = ['message' => "Buscando TMDB para: {$item['name']}", 'type' => 'tmdb'];
                    $tmdb_type = ($content_type === 'movie') ? 'movie' : 'tv';
                    $tmdb_match = TMDB::findBestMatch($tmdb_type, $item['name']);
                    
                    if ($tmdb_match) {
                        $tmdb_details = TMDB::getDetails($tmdb_type, $tmdb_match['id']);
                        $dataToInsert['icon'] = $tmdb_details['stream_icon'] ?? $tmdb_details['cover'];
                        $dataToInsert = array_merge($dataToInsert, $tmdb_details);
                        if (!empty($tmdb_details['releaseDate'])) {
                            $release_year = substr($tmdb_details['releaseDate'], 0, 4);
                        }
                    } else {
                        $log[] = ['message' => "TMDB não encontrou: {$item['name']}", 'type' => 'warning'];
                        $dataToInsert['icon'] = $item['logo'];
                    }
                    
                    $dataToInsert['cat_id'] = $category_id;
                    if ($content_type === 'movie') {
                        $dataToInsert['type'] = 'movie';
                        $dataToInsert['url'] = $item['url'];
                    }
                }
                
                $sql_cols = []; $sql_placeholders = []; $params = [];
                foreach ($config['columns'] as $key => $colName) {
                    if (isset($dataToInsert[$key])) {
                        $sql_cols[] = "`" . $colName . "`";
                        $sql_placeholders[] = '?';
                        $params[] = $dataToInsert[$key];
                    }
                }
                
                if (count($sql_cols) <= 1) continue;
                
                $sql = "INSERT INTO `{$tabela}` (" . implode(', ', $sql_cols) . ") VALUES (" . implode(', ', $sql_placeholders) . ")";
                $stmt_insert = $pdo->prepare($sql);
                $stmt_insert->execute($params);
                
                $log[] = ['message' => "Adicionado '{$item['name']}' na categoria '{$item['group']}'", 'type' => 'success'];
                $added_names[$content_type][] = $item['name'];

                // Adiciona a linha formatada ao arquivo de relatório
                $year_str = $release_year ? " ({$release_year})" : "";
                $report_line = "- {$item['name']}{$year_str} [{$item['group']}]" . PHP_EOL;
                file_put_contents($report_file, $report_line, FILE_APPEND);

            } catch (PDOException $e) {
                error_log("Erro ao salvar {$item['name']}: " . $e->getMessage());
                $log[] = ['message' => "Erro ao salvar {$item['name']}: " . $e->getMessage(), 'type' => 'error'];
            }
        }
        
        $final_report = null;
        if (($offset + count($playlist_chunk)) >= count($playlist)) {
            if (file_exists($report_file)) {
                $final_report = "✅ **Relatório de Importação Concluída** ✅" . PHP_EOL . PHP_EOL;
                $final_report .= file_get_contents($report_file);
                unlink($report_file);
            }
            unlink($task_file);
        }

        echo json_encode([
            'success' => true,
            'log' => $log,
            'added_names' => $added_names,
            'processed_count' => count($playlist_chunk),
            'final_report' => $final_report
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Passo inválido.']);
        break;
}
?>