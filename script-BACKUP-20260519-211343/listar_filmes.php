<?php
// Habilitar erros apenas em desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar cabeçalhos PRIMEIRO, antes de qualquer output
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Server: nginx");

// Conexão com o banco de dados
require_once("api/controles/db.php");
$conexao = conectar_bd();

// Verificar se a conexão foi estabelecida corretamente
if (!$conexao) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Falha na conexão com o banco de dados'
    ]);
    exit();
}

// Verificar se a conexão está ativa
try {
    $conexao->query("SELECT 1");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro na conexão com o banco de dados: ' . $e->getMessage()
    ]);
    exit();
}

/**
 * Busca informações do filme no TMDb
 * 
 * @param string $titulo Título do filme a buscar
 * @return array|null Dados do filme ou null se não encontrado
 */
function buscarTmdb($titulo) {
    $api_key = '50dcb709df1cd8ab0d6399ea2de9c04e';
    
    try {
        // 1. Primeiro fazemos a busca pelo filme
        $urlBusca = "https://api.themoviedb.org/3/search/movie?api_key={$api_key}&query=" . urlencode($titulo) . "&language=pt-BR";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlBusca,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FAILONERROR => true
        ]);
        
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("Erro na busca: " . curl_error($ch));
        }
        curl_close($ch);
        
        $dadosBusca = json_decode($response, true);
        if (!$dadosBusca || !isset($dadosBusca['results'][0]['id'])) {
            return null;
        }
        
        $filmeId = $dadosBusca['results'][0]['id'];
        
        // 2. Agora buscamos os detalhes COMPLETOS do filme
        $urlDetalhes = "https://api.themoviedb.org/3/movie/{$filmeId}?api_key={$api_key}&language=pt-BR&append_to_response=credits,videos";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlDetalhes,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FAILONERROR => true
        ]);
        
        $responseDetalhes = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("Erro nos detalhes: " . curl_error($ch));
        }
        curl_close($ch);
        
        $detalhes = json_decode($responseDetalhes, true);
        if (!$detalhes) {
            return null;
        }
        
        // 3. Processamos todos os dados que precisamos
        $dadosFilme = [
            'plot' => $detalhes['overview'] ?? '',
            'genre' => isset($detalhes['genres']) ? implode(', ', array_column($detalhes['genres'], 'name')) : '',
            'releasedate' => $detalhes['release_date'] ?? '',
            'backdrop_path' => isset($detalhes['backdrop_path']) ? 'https://image.tmdb.org/t/p/original' . $detalhes['backdrop_path'] : '',
            'rating' => $detalhes['vote_average'] ?? 0,
            'rating_5based' => isset($detalhes['vote_average']) ? round($detalhes['vote_average'] / 2, 1) : 0,
            'year' => isset($detalhes['release_date']) ? substr($detalhes['release_date'], 0, 4) : '',
            'youtube_trailer' => '',
            'actors' => ''
        ];
        
        // 4. Processar elenco (atores)
        if (isset($detalhes['credits']['cast'])) {
            $atores = array_slice($detalhes['credits']['cast'], 0, 5);
            $dadosFilme['actors'] = implode(', ', array_column($atores, 'name'));
        }
        
        // 5. Processar trailer
        if (isset($detalhes['videos']['results'])) {
            foreach ($detalhes['videos']['results'] as $video) {
                if (strtolower($video['type']) === 'trailer' && $video['site'] === 'YouTube') {
                    $dadosFilme['youtube_trailer'] = 'https://www.youtube.com/watch?v=' . $video['key'];
                    break;
                }
            }
        }
        
        // DEBUG: Verificar os dados obtidos
        error_log("Dados obtidos para '{$titulo}': " . print_r($dadosFilme, true));
        
        return $dadosFilme;
        
    } catch (Exception $e) {
        error_log("Erro em buscarTmdb('{$titulo}'): " . $e->getMessage());
        return null;
    }
}

/**
 * Atualiza os dados de um filme no banco de dados
 * 
 * @param int $id ID do filme
 * @param array $dados Dados do filme
 * @return bool True se atualizado com sucesso
 */
function atualizarFilme($id, array $dados) {
    global $conexao;

    $query = "UPDATE streams SET 
              genre = :genre, 
              actors = :actors, 
              plot = :plot, 
              backdrop_path = :backdrop_path, 
              releasedate = :releasedate, 
              rating = :rating, 
              rating_5based = :rating_5based, 
              year = :year 
              WHERE id = :id";

    try {
        $stmt = $conexao->prepare($query);
        
        return $stmt->execute([
            ':id' => $id,
            ':genre' => $dados['genre'] ?? '',
            ':actors' => $dados['actors'] ?? '',
            ':plot' => $dados['plot'] ?? '',
            ':backdrop_path' => $dados['backdrop_path'] ?? '',
            ':releasedate' => $dados['releasedate'] ?? '',
            ':rating' => $dados['rating'] ?? 0,
            ':rating_5based' => $dados['rating_5based'] ?? 0,
            ':year' => $dados['year'] ?? ''
        ]);
        
    } catch (PDOException $e) {
        error_log("Erro ao atualizar filme ID {$id}: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualiza todos os filmes com dados do TMDb
 */
function atualizarTodosOsFilmes() {
    global $conexao;

    try {
        // Limitar a 100 filmes por execução para evitar timeout
        $stmt = $conexao->query("SELECT id, name FROM streams ORDER BY id ASC LIMIT 100");
        $filmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $atualizados = 0;
        $erros = 0;

        foreach ($filmes as $filme) {
            $tmdb = buscarTmdb($filme['name']);
            
            if ($tmdb && atualizarFilme($filme['id'], $tmdb)) {
                $atualizados++;
                // Pequena pausa para evitar rate limit da API
                usleep(200000); // 200ms
            } else {
                $erros++;
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Atualização parcial concluída',
            'atualizados' => $atualizados,
            'erros' => $erros,
            'total' => count($filmes)
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Erro durante a atualização: ' . $e->getMessage()
        ]);
    }
}

// Executar a atualização
atualizarTodosOsFilmes();