<?php
// ARQUIVO: /gerenciador/ajax/filme_buscar_tmdb.php (VERSÃO FINAL)
header('Content-Type: application/json');

// !!! COLOQUE SUA CHAVE DA API DO TMDB AQUI !!!
$apiKey = 'f99aa9ae1fe7619969cc7db0938c1ae5';

$query = $_GET['query'] ?? '';

if (empty($apiKey) || $apiKey === 'SUA_CHAVE_API_DO_TMDB_VAI_AQUI') {
    echo json_encode(['error' => 'A chave da API do TMDB não foi configurada no backend.']);
    exit;
}
if (empty($query)) { echo json_encode([]); exit; }

// Busca por filmes na API
$url = "https://api.themoviedb.org/3/search/movie?api_key=" . urlencode($apiKey) . "&language=pt-BR&query=" . urlencode($query);
$response = @file_get_contents($url);
if ($response === false) {
    echo json_encode(['error' => 'Não foi possível se comunicar com a API do TMDB.']);
    exit;
}
echo $response;
?>