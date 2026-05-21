<?php
require_once 'db.php'; // Aqui está sua conexão PDO (conforme já usa)

// Função para buscar dados da TMDb
function fetchTmdbData($tmdb_id) {
    $apiKey = 'b1f31aa6b974a3d7430e6b444af8649f'; // Substitua pela sua chave
    $url = "https://api.themoviedb.org/3/movie/{$tmdb_id}?language=pt-BR&api_key={$apiKey}";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// Quando botão for clicado
if (isset($_POST['atualizar'])) {
    $stmt = $pdo->query("SELECT id, tmdb_id FROM streams WHERE tmdb_id IS NOT NULL");
    $filmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $atualizados = 0;

    foreach ($filmes as $filme) {
        $dados = fetchTmdbData($filme['tmdb_id']);
        if (!$dados || isset($dados['status_code'])) continue;

        $update = $pdo->prepare("UPDATE streams SET
            titulo = :titulo,
            sinopse = :sinopse,
            banner = :banner,
            poster = :poster,
            ano = :ano,
            nota = :nota,
            duracao = :duracao,
            generos = :generos
            WHERE id = :id
        ");

        $update->execute([
            ':titulo' => $dados['title'] ?? '',
            ':sinopse' => $dados['overview'] ?? '',
            ':banner' => 'https://image.tmdb.org/t/p/original' . ($dados['backdrop_path'] ?? ''),
            ':poster' => 'https://image.tmdb.org/t/p/w500' . ($dados['poster_path'] ?? ''),
            ':ano' => substr($dados['release_date'] ?? '', 0, 4),
            ':nota' => $dados['vote_average'] ?? 0,
            ':duracao' => $dados['runtime'] ?? 0,
            ':generos' => implode(', ', array_map(fn($g) => $g['name'], $dados['genres'] ?? [])),
            ':id' => $filme['id']
        ]);

        $atualizados++;
    }

    echo "<div class='alert alert-success mt-3'>Filmes atualizados: {$atualizados}</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Atualizar Filmes TMDb</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f0f4f8;
      font-family: "Segoe UI", sans-serif;
    }
    .container {
      margin-top: 60px;
      max-width: 600px;
    }
    .card {
      border: none;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      border-radius: 16px;
    }
    .btn-primary {
      border-radius: 30px;
      padding: 10px 30px;
      font-weight: bold;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="card p-4">
    <h2 class="mb-4 text-center">Atualizar Informações da TMDb</h2>
    <form method="post">
      <p>Ao clicar no botão abaixo, o sistema buscará os dados atualizados da TMDb para todos os filmes registrados.</p>
      <div class="text-center mt-4">
        <button type="submit" name="atualizar" class="btn btn-primary">Atualizar Filmes</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
