<?php
header('Content-Type: application/json'); // Informa que a resposta será em formato JSON

// Sua chave da API do TMDB
$apiKey = 'coloca_sua_api_tmdb_aqui';
$query = isset($_GET['query']) ? urlencode($_GET['query']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'movie'; // 'movie' ou 'tv' para séries

if (empty($query)) {
    echo json_encode(['error' => 'Nenhum termo de busca fornecido.']);
    exit;
}

// Monta a URL da API
$url = "https://api.themoviedb.org/3/search/{$type}?api_key={$apiKey}&language=pt-BR&query={$query}";

// Faz a requisição e retorna o resultado
$response = file_get_contents($url);
echo $response;
?>