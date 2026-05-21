<?php
// ARQUIVO: /gerenciador/filme_editar.php (NOVO PAINEL - COM BUSCA TMDB)
require_once('../api/controles/db.php'); $pdo = conectar_bd();
$id = $_GET['id'] ?? null; if (!$id) { die("ID não fornecido."); }
// Busca na tabela 'streams'
$stmt = $pdo->prepare("SELECT * FROM streams WHERE id = :id AND stream_type = 'movie'");
$stmt->execute([':id' => $id]);
$filme = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$filme) { die("Filme não encontrado."); }
$categorias = $pdo->query("SELECT id, nome FROM categoria ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Filme: <?= htmlspecialchars($filme['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; } .card { border: none; border-radius: 0.75rem; } .tmdb-result { cursor: pointer; } .tmdb-result:hover { background-color: #e9ecef; } .tmdb-result img { width: 70px; border-radius: 5px; }
        body.dark-mode { background-color: #121212; color: #e0e0e0; }
        body.dark-mode .card { background-color: #1e1e1e; border: 1px solid #2c2c2c; }
        body.dark-mode .card-header, body.dark-mode h4, body.dark-mode h5 { background-color: #1e1e1e !important; color: #ffffff; }
        body.dark-mode .form-control, body.dark-mode .form-select, body.dark-mode .list-group-item { background-color: #2a2a2a; color: #e0e0e0; border-color: #3c3c3c; }
        body.dark-mode .tmdb-result:hover { background-color: #2c2c2c; }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="card">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-pencil-alt me-2"></i>Editar Filme</h4>
                <a href="filmes.php" class="btn btn-secondary">Voltar para a Lista</a>
            </div>
            <div class="card-body p-4">

                <h5 class="mb-3">Atualizar informações pelo TMDB (Opcional)</h5>
                <div class="input-group mb-3"><input type="text" id="tmdbSearchInput" class="form-control" placeholder="Digite o nome do filme para buscar..."><button class="btn btn-primary" type="button" id="searchButton">Buscar</button></div>
                <div id="tmdbResults" class="list-group" style="max-height: 400px; overflow-y: auto;"></div>
                <hr class="my-4">

                <h5 class="mb-3">Dados do Filme</h5>
                <form id="editMovieForm">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($filme['id']) ?>">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3"><label for="name" class="form-label">Título</label><input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($filme['name']) ?>" required></div>
                            <div class="mb-3"><label for="plot" class="form-label">Sinopse</label><textarea class="form-control" id="plot" name="plot" rows="5"><?= htmlspecialchars($filme['plot']) ?></textarea></div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3"><label class="form-label">Capa (Poster)</label><img id="posterPreview" src="<?= htmlspecialchars($filme['stream_icon']) ?>" class="img-fluid rounded mb-2"><input type="text" class="form-control" id="stream_icon" name="stream_icon" value="<?= htmlspecialchars($filme['stream_icon']) ?>"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"><label for="tmdb_id" class="form-label">TMDB ID</label><input type="text" class="form-control" id="tmdb_id" name="tmdb_id" value="<?= htmlspecialchars($filme['tmdb_id']) ?>"></div>
                        <div class="col-md-4"><label for="year" class="form-label">Ano</label><input type="text" class="form-control" id="year" name="year" value="<?= htmlspecialchars($filme['year']) ?>"></div>
                        <div class="col-md-4"><label for="category_id" class="form-label">Categoria</label><select class="form-select" id="category_id" name="category_id" required><option value="">Selecione</option><?php foreach ($categorias as $cat): ?><option value="<?= htmlspecialchars($cat['id']) ?>" <?= ($filme['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option><?php endforeach; ?></select></div>
                    </div>
                    <hr class="my-4">
                    <div class="mb-3"><label for="link" class="form-label"><strong>URL do Stream (Link do Filme)</strong></label><input type="text" class="form-control" id="link" name="link" value="<?= htmlspecialchars($filme['link']) ?>" required></div>
                    <div class="d-grid"><button type="submit" class="btn btn-success btn-lg">Salvar Alterações</button></div>
                </form>
            </div>
        </div>
    </div>
<script>
    // --- JAVASCRIPT DO TEMA ---
    (function() { const themeKey = 'theme'; function applyTheme() { const savedTheme = localStorage.getItem(themeKey); document.body.classList.toggle('dark-mode', savedTheme === 'dark'); } applyTheme(); })();

    // --- JAVASCRIPT DA BUSCA TMDB (ADICIONADO AQUI) ---
    async function searchTmdb() {
        const query = document.getElementById('tmdbSearchInput').value;
        const resultsDiv = document.getElementById('tmdbResults');
        if (query.length < 2) { resultsDiv.innerHTML = ''; return; }
        resultsDiv.innerHTML = '<div class="list-group-item">Buscando...</div>';
        try {
            const response = await fetch(`ajax/filme_buscar_tmdb.php?query=${query}`);
            const data = await response.json();
            resultsDiv.innerHTML = '';
            if (data.error) {
                resultsDiv.innerHTML = `<div class="list-group-item list-group-item-danger">${data.error}</div>`;
                return;
            }
            if (data.results && data.results.length > 0) {
                data.results.forEach(movie => {
                    const posterUrl = movie.poster_path ? `https://image.tmdb.org/t/p/w200${movie.poster_path}` : 'https://via.placeholder.com/70x105';
                    const releaseYear = movie.release_date ? `(${movie.release_date.split('-')[0]})` : '';
                    const resultItem = document.createElement('a');
                    resultItem.href = '#';
                    resultItem.className = 'list-group-item list-group-item-action tmdb-result d-flex align-items-center';
                    resultItem.innerHTML = `<img src="${posterUrl}" class="me-3"><div><strong>${movie.title}</strong> ${releaseYear}<p class="mb-0 text-muted small">${movie.overview.substring(0, 100)}...</p></div>`;
                    resultItem.onclick = (e) => { e.preventDefault(); fillFormWithTmdbData(movie); };
                    resultsDiv.appendChild(resultItem);
                });
            } else {
                resultsDiv.innerHTML = '<div class="list-group-item">Nenhum resultado encontrado.</div>';
            }
        } catch (error) {
            resultsDiv.innerHTML = `<div class="list-group-item list-group-item-danger">Ocorreu um erro na busca.</div>`;
        }
    }
    function fillFormWithTmdbData(movie) {
        document.getElementById('name').value = movie.title;
        document.getElementById('plot').value = movie.overview;
        document.getElementById('tmdb_id').value = movie.id;
        document.getElementById('year').value = movie.release_date ? movie.release_date.split('-')[0] : '';
        const posterUrl = movie.poster_path ? `https://image.tmdb.org/t/p/w500${movie.poster_path}` : '';
        document.getElementById('stream_icon').value = posterUrl;
        document.getElementById('posterPreview').src = posterUrl || 'https://via.placeholder.com/200x300';
    }
    document.getElementById('searchButton').addEventListener('click', searchTmdb);
    document.getElementById('tmdbSearchInput').addEventListener('keyup', (e) => { if (e.key === 'Enter') searchTmdb(); });

    // --- JAVASCRIPT PARA SALVAR AS ALTERAÇÕES ---
    document.getElementById('editMovieForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = 'Salvando...';
        try {
            const response = await fetch('ajax/filme_atualizar.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                window.location.href = 'filmes.php';
            }
        } catch (err) {
            alert('Erro de comunicação.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Salvar Alterações';
        }
    });
</script>
</body>
</html>