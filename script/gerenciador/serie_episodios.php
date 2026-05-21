<?php
// ARQUIVO: /gerenciador/serie_episodios.php (VERSÃO FINAL COMPLETA)
require_once('../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { die("Falha fatal ao conectar ao banco de dados."); }

$serie_id = $_GET['id'] ?? null;
if (!$serie_id) { die("ID da série não fornecido."); }

$stmtSerie = $pdo->prepare("SELECT name FROM series WHERE id = :id");
$stmtSerie->execute([':id' => $serie_id]);
$serie = $stmtSerie->fetch(PDO::FETCH_ASSOC);

$stmtEpisodes = $pdo->prepare("SELECT * FROM series_episodes WHERE series_id = :series_id ORDER BY season, episode_num");
$stmtEpisodes->execute([':series_id' => $serie_id]);
$episodes = $stmtEpisodes->fetchAll(PDO::FETCH_ASSOC);

$seasons = [];
foreach ($episodes as $ep) {
    $seasons[$ep['season']][] = $ep;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Episódios de: <?= htmlspecialchars($serie['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .card { border: none; border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .accordion-button:not(.collapsed) { background-color: #e7f1ff; }

        /* --- CSS PARA O TEMA ESCURO --- */
        body.dark-mode { background-color: #121212; color: #e0e0e0; }
        body.dark-mode .card { background-color: #1e1e1e; border: 1px solid #2c2c2c; }
        body.dark-mode .card-header, body.dark-mode h4, body.dark-mode h5 { background-color: #1e1e1e !important; color: #ffffff; }
        body.dark-mode .form-control, body.dark-mode .form-select { background-color: #2a2a2a; color: #e0e0e0; border-color: #3c3c3c; }
        body.dark-mode .accordion-item { background-color: #1e1e1e; border-color: #3c3c3c; }
        body.dark-mode .accordion-button { background-color: #2a2a2a; color: #e0e0e0; }
        body.dark-mode .accordion-button:not(.collapsed) { background-color: #0d6efd; color: white; }
        body.dark-mode .accordion-button::after { filter: invert(1) grayscale(100%) brightness(200%); }
        body.dark-mode .list-group-item { background-color: #1e1e1e; border-color: #3c3c3c; }
        body.dark-mode .modal-content { background-color: #1e1e1e; }
        body.dark-mode .modal-header, body.dark-mode .modal-footer { border-color: #3c3c3c; }
        body.dark-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Adicionar Novo Episódio</h5></div>
            <div class="card-body">
                <form id="addEpisodeForm">
                    <input type="hidden" name="series_id" value="<?= $serie_id ?>">
                    <div class="row g-3">
                        <div class="col-md-2"><input type="number" name="season_num" class="form-control" placeholder="Temporada" required></div>
                        <div class="col-md-2"><input type="number" name="episode_num" class="form-control" placeholder="Episódio" required></div>
                        <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="Título do Episódio" required></div>
                        <div class="col-md-4"><input type="text" name="stream_url" class="form-control" placeholder="URL do Stream" required></div>
                        <div class="col-12 d-grid"><button type="submit" class="btn btn-primary">Adicionar Episódio</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista de Episódios</h5>
                <a href="series.php" class="btn btn-secondary btn-sm">Voltar para a Lista de Séries</a>
            </div>
            <div class="card-body">
                <?php if (empty($seasons)): ?>
                    <p class="text-center">Nenhum episódio cadastrado para esta série.</p>
                <?php else: ?>
                    <div class="accordion" id="seasonsAccordion">
                        <?php foreach ($seasons as $season_num => $ep_list): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $season_num ?>">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $season_num ?>">
                                    Temporada <?= $season_num ?> (<?= count($ep_list) ?> episódios)
                                </button>
                            </h2>
                            <div id="collapse<?= $season_num ?>" class="accordion-collapse collapse show">
                                <div class="accordion-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($ep_list as $ep): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center" id="episode-row-<?= $ep['id'] ?>">
                                            <span>S<?= str_pad($ep['season'], 2, '0', STR_PAD_LEFT) ?>E<?= str_pad($ep['episode_num'], 2, '0', STR_PAD_LEFT) ?>: <?= htmlspecialchars($ep['title']) ?></span>
                                            <div>
                                                <button onclick='openEditModal(<?= htmlspecialchars(json_encode($ep), ENT_QUOTES, 'UTF-8') ?>)' class="btn btn-primary btn-sm" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                                                <button onclick="deleteEpisode(<?= $ep['id'] ?>)" class="btn btn-danger btn-sm" title="Excluir"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editEpisodeModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Editar Episódio</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="editEpisodeForm">
                <input type="hidden" name="id" id="edit-episode-id">
                <div class="mb-3"><label>Temporada</label><input type="number" name="season_num" id="edit-season-num" class="form-control" required></div>
                <div class="mb-3"><label>Episódio</label><input type="number" name="episode_num" id="edit-episode-num" class="form-control" required></div>
                <div class="mb-3"><label>Título</label><input type="text" name="title" id="edit-title" class="form-control" required></div>
                <div class="mb-3"><label>URL do Stream</label><input type="text" name="stream_url" id="edit-stream-url" class="form-control" required></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="saveEpisodeChanges()">Salvar Alterações</button>
          </div>
        </div>
      </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

// --- JAVASCRIPT DAS AÇÕES ---
const editModal = new bootstrap.Modal(document.getElementById('editEpisodeModal'));
document.getElementById('addEpisodeForm').addEventListener('submit', async function(e) { e.preventDefault(); const formData = new FormData(this); const response = await fetch('ajax/episodio_adicionar.php', { method: 'POST', body: formData }); const result = await response.json(); alert(result.message); if (result.success) location.reload(); });
async function deleteEpisode(id) { if (!confirm('Tem certeza?')) return; const formData = new FormData(); formData.append('id', id); const response = await fetch('ajax/episodio_excluir.php', { method: 'POST', body: formData }); const result = await response.json(); alert(result.message); if (result.success) document.getElementById(`episode-row-${id}`).remove(); }
function openEditModal(episodeData) {
    document.getElementById('edit-episode-id').value = episodeData.id;
    document.getElementById('edit-season-num').value = episodeData.season;
    document.getElementById('edit-episode-num').value = episodeData.episode_num;
    document.getElementById('edit-title').value = episodeData.title;
    document.getElementById('edit-stream-url').value = episodeData.link;
    editModal.show();
}
async function saveEpisodeChanges() {
    const form = document.getElementById('editEpisodeForm');
    const formData = new FormData(form);
    const submitButton = document.querySelector('#editEpisodeModal .btn-primary');
    submitButton.disabled = true;
    try {
        const response = await fetch('ajax/episodio_atualizar.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            editModal.hide();
            location.reload();
        }
    } catch (error) {
        alert('Ocorreu um erro de comunicação.');
        console.error('Save Error:', error);
    } finally {
        submitButton.disabled = false;
    }
}
</script>
</body>
</html>