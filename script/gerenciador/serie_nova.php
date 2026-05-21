<?php
// ARQUIVO: /gerenciador/serie_nova.php (VERSÃO FINAL COMPLETA)

require_once('../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { die("Falha fatal ao conectar ao banco de dados."); }
$categorias = $pdo->query("SELECT id, nome FROM categoria ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Nova Série</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .card { border: none; border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .tmdb-result { cursor: pointer; transition: background-color 0.2s; }
        .tmdb-result:hover { background-color: #e9ecef; }
        .tmdb-result img { width: 70px; border-radius: 5px; }
        
        /* --- CSS PARA O TEMA ESCURO --- */
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
                <h4 class="mb-0"><i class="fas fa-plus me-2"></i>Adicionar Nova Série</h4>
                <a href="series.php" class="btn btn-secondary">Voltar para a Lista</a>
            </div>
            <div class="card-body p-4">
                <h5 class="mb-3">Etapa 1: Buscar no TMDB</h5>
                <div class="input-group mb-3">
                    <input type="text" id="tmdbSearchInput" class="form-control" placeholder="Digite o nome da série...">
                    <button class="btn btn-primary" type="button" id="searchButton">Buscar</button>
                </div>
                <div id="tmdbResults" class="list-group" style="max-height: 400px; overflow-y: auto;"></div>
                <hr class="my-4">

                <h5 class="mb-3">Etapa 2: Preencher Dados e Salvar</h5>
                <form id="addSerieForm">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome da Série</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="plot" class="form-label">Sinopse</label>
                                <textarea class="form-control" id="plot" name="plot" rows="5"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Capa (Poster)</label>
                                <img id="posterPreview" src="https://via.placeholder.com/200x300" class="img-fluid rounded mb-2">
                                <input type="text" class="form-control" id="cover" name="cover">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="release_date" class="form-label">Data de Lançamento</label>
                            <input type="date" class="form-control" id="release_date" name="release_date">
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Categoria</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">Salvar Série</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
// --- JAVASCRIPT DO TEMA ---
(function() {
    const themeKey = 'theme'; 
    function applyTheme() {
        const savedTheme = localStorage.getItem(themeKey);
        document.body.classList.toggle('dark-mode', savedTheme === 'dark');
    }
    applyTheme();
    window.addEventListener('storage', e => { if (e.key === themeKey) applyTheme(); });
    setInterval(applyTheme, 2000);
})();

// --- FUNÇÕES DE BUSCA E PREENCHIMENTO ---
async function searchTmdb() {
    const query = document.getElementById('tmdbSearchInput').value;
    const resultsDiv = document.getElementById('tmdbResults');
    if (query.length < 2) { resultsDiv.innerHTML = ''; return; }
    resultsDiv.innerHTML = '<div class="list-group-item">Buscando...</div>';
    try {
        const response = await fetch(`ajax/serie_buscar_tmdb.php?query=${query}`);
        const data = await response.json();
        resultsDiv.innerHTML = '';
        if (data.error) {
            resultsDiv.innerHTML = `<div class="list-group-item list-group-item-danger">${data.error}</div>`;
            return;
        }
        if (data.results && data.results.length > 0) {
            data.results.forEach(serie => {
                const posterUrl = serie.poster_path ? `https://image.tmdb.org/t/p/w200${serie.poster_path}` : 'https://via.placeholder.com/70x105';
                const releaseYear = serie.first_air_date ? `(${serie.first_air_date.split('-')[0]})` : '';
                const resultItem = document.createElement('a');
                resultItem.href = '#';
                resultItem.className = 'list-group-item list-group-item-action tmdb-result d-flex align-items-center';
                resultItem.innerHTML = `<img src="${posterUrl}" class="me-3"><div><strong>${serie.name}</strong> ${releaseYear}<p class="mb-0 text-muted small">${serie.overview.substring(0, 100)}...</p></div>`;
                resultItem.onclick = (e) => { e.preventDefault(); fillFormWithTmdbData(serie); };
                resultsDiv.appendChild(resultItem);
            });
        } else {
            resultsDiv.innerHTML = '<div class="list-group-item">Nenhuma série encontrada.</div>';
        }
    } catch (error) {
        resultsDiv.innerHTML = `<div class="list-group-item list-group-item-danger">Ocorreu um erro na busca.</div>`;
    }
}
function fillFormWithTmdbData(serie) {
    document.getElementById('name').value = serie.name;
    document.getElementById('plot').value = serie.overview;
    document.getElementById('release_date').value = serie.first_air_date;
    const posterUrl = serie.poster_path ? `https://image.tmdb.org/t/p/w500${serie.poster_path}` : '';
    document.getElementById('cover').value = posterUrl;
    document.getElementById('posterPreview').src = posterUrl || 'https://via.placeholder.com/200x300';
    document.getElementById('addSerieForm').scrollIntoView({ behavior: 'smooth' });
}
document.getElementById('searchButton').addEventListener('click', searchTmdb);
document.getElementById('tmdbSearchInput').addEventListener('keyup', (event) => { if (event.key === 'Enter') searchTmdb(); });

// --- FUNÇÃO PARA SALVAR O FORMULÁRIO ---
document.getElementById('addSerieForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = 'Salvando...';
    try {
        const response = await fetch('ajax/serie_salvar.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            form.reset();
            document.getElementById('posterPreview').src = 'https://via.placeholder.com/200x300';
        }
    } catch (error) {
        alert('Ocorreu um erro de comunicação ao tentar salvar a série.');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Salvar Série';
    }
});
</script>

</body>
</html>