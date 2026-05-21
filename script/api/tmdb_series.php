<?php
// REMOVIDA TODA E QUALQUER VERIFICAÇÃO DE SESSÃO DESTE ARQUIVO
// A segurança é garantida pela página que o chama.

// As duas linhas abaixo foram REMOVIDAS pois são redundantes e causam o erro de caminho.
// O script principal (atualizar_tmdb_final.php) já faz essas inclusões.
// require_once('./controles/db.php');
// require_once('../models/TMDB.php');

header('Content-Type: application/json; charset=utf-8');

if (function_exists('conectar_bd')) {
    $pdo = conectar_bd();
    if ($pdo === null) {
        echo json_encode(['error' => 'Falha ao conectar com o banco de dados. Verifique as credenciais em db.php.']);
        exit();
    }
}
else {
    echo json_encode(['error' => 'Função de conexão com o banco não encontrada.']);
    exit();
}

if (isset($_GET['get_series'])) {
    echo json_encode(\models\TMDB::getSerie($_GET['get_series']));
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'listar') {
    try {
        $stmt = $pdo->query("SELECT id, name FROM series WHERE tmdb_id IS NULL OR tmdb_id = ''");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'atualizar') {
    $stmt = $pdo->prepare("UPDATE series SET tmdb_id = ? WHERE id = ?");
    $stmt->execute([$_POST['tmdb_id'], (int)$_POST['id']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => "Nenhuma série encontrada com o ID local {$_POST['id']}."]);
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'atualizar_infos') {
    try {
        $stmt = $pdo->query("SELECT id, name, tmdb_id FROM series WHERE tmdb_id IS NOT NULL AND tmdb_id != ''");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'salvar_detalhes') {
    $stmt = $pdo->prepare("UPDATE series SET name = :titulo, plot = :plot, cover = :cover, backdrop_path = :backdrop_path, releaseDate = :release_date, rating = :rating, rating_5based = :rating_5based, year = :year, genre = :genre, director = :director, cast = :cast, youtube_trailer = :youtube_trailer WHERE id = :id");
    $stmt->execute([':titulo' => $_POST['titulo'] ?? null, ':plot' => $_POST['plot'] ?? null, ':cover' => $_POST['cover'] ?? null, ':backdrop_path' => $_POST['backdrop_path'] ?? null, ':release_date' => $_POST['release_date'] ?? null, ':rating' => $_POST['rating'] ?? null, ':rating_5based' => $_POST['rating_5based'] ?? null, ':year' => $_POST['year'] ?? null, ':genre' => $_POST['genre'] ?? null, ':director' => $_POST['director'] ?? null, ':cast' => $_POST['cast'] ?? null, ':youtube_trailer' => $_POST['youtube_trailer'] ?? null, ':id' => $_POST['id'] ?? null]);
    echo json_encode(['success' => true]);
    exit();
}