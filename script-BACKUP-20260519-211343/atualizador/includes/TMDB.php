<?php
// includes/TMDB.php

class TMDB {
    // --- COLOQUE SUA CHAVE DA API DO TMDB AQUI ---
    private static $apiKey = '3b4d953e1de069ca813d613a6a3eedd4';
    // ---------------------------------------------

    /**
     * Função base para fazer requisições à API do TMDB.
     */
    private static function fetch($endpoint, $params = []) {
        $params['api_key'] = self::$apiKey;
        $params['language'] = 'pt-BR';
        $url = 'https://api.themoviedb.org/3/' . $endpoint . '?' . http_build_query($params);
        
        // Usando file_get_contents para maior compatibilidade com hospedagens cPanel
        $json = @file_get_contents($url);
        if ($json === false) {
            return null;
        }
        return json_decode($json, true);
    }

    // =========================================================================
    //  FUNÇÃO ATUALIZADA PARA MELHORAR A BUSCA
    //  Esta função limpa o nome do filme/série antes de buscar,
    //  removendo tags como (2024), [DUBLADO], 1080p, etc.
    // =========================================================================
    public static function findBestMatch($type, $name) {
        // Lista de palavras e padrões para remover do título
        $patternsToRemove = [
            '/\((19|20)\d{2}\)/', // Remove o ano entre parênteses, ex: (2024)
            '/\[.*?\]/',          // Remove qualquer coisa entre colchetes, ex: [DUBLADO], [LEGENDADO]
            '/\b(1080p|720p|4k|HD|WEB-DL|DUBLADO|LEGENDADO|LEG|DUAL|NACIONAL)\b/i' // Remove tags de qualidade/idioma
        ];
        
        $cleanName = preg_replace($patternsToRemove, '', $name);
        $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName)); // Remove espaços duplicados e no início/fim

        // Se a limpeza remover tudo (em casos raros), usa o nome original
        if (empty($cleanName)) {
            $cleanName = $name;
        }

        $data = self::fetch("search/{$type}", ['query' => $cleanName, 'include_adult' => 'true']);
        return $data['results'][0] ?? null; // Retorna o primeiro resultado, que geralmente é o melhor
    }

    /**
     * Busca os detalhes completos de um filme ou série usando o ID do TMDB.
     */
    public static function getDetails($type, $tmdbId) {
        $data = self::fetch("{$type}/{$tmdbId}", ['append_to_response' => 'credits']);
        if (!$data) return null;

        $details = [];
        $imageBaseUrl = 'https://image.tmdb.org/t/p/w500';

        if ($type === 'movie') {
            $director = '';
            foreach ($data['credits']['crew'] ?? [] as $crew) {
                if ($crew['job'] == 'Director') {
                    $director = $crew['name'];
                    break;
                }
            }
            $details['name'] = $data['title'] ?? '';
            $details['stream_icon'] = $data['poster_path'] ? $imageBaseUrl . $data['poster_path'] : '';
            $details['releaseDate'] = $data['release_date'] ?? '';
            $details['actors'] = implode(', ', array_slice(array_column($data['credits']['cast'] ?? [], 'name'), 0, 5));
            $details['duration'] = isset($data['runtime']) && $data['runtime'] > 0 ? gmdate("H:i:s", $data['runtime'] * 60) : '00:00:00';

        } else { // 'tv' (series)
            $details['name'] = $data['name'] ?? '';
            $details['cover'] = $data['poster_path'] ? $imageBaseUrl . $data['poster_path'] : '';
            $details['releaseDate'] = $data['first_air_date'] ?? '';
            $details['cast'] = implode(', ', array_slice(array_column($data['credits']['cast'] ?? [], 'name'), 0, 5));
        }

        // Campos comuns para ambos
        $details['plot'] = $data['overview'] ?? '';
        $details['backdrop_path'] = $data['backdrop_path'] ? 'https://image.tmdb.org/t/p/original' . $data['backdrop_path'] : '';
        $details['rating'] = $data['vote_average'] ?? 0;
        $details['rating_5based'] = round(($data['vote_average'] ?? 0) / 2, 1);
        $details['year'] = !empty($details['releaseDate']) ? substr($details['releaseDate'], 0, 4) : '';
        $details['genre'] = implode(', ', array_column($data['genres'] ?? [], 'name'));
        $details['director'] = $director ?? implode(', ', array_column($data['created_by'] ?? [], 'name'));
        $details['youtube_trailer'] = ''; // TMDB API v3 não fornece trailer facilmente, deixado em branco por simplicidade

        return $details;
    }
}
?>