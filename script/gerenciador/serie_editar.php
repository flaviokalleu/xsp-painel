<?php
// ARQUIVO: /gerenciador/serie_editar.php (VERSÃO FINAL COMPLETA)

require_once('../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { die("Falha fatal ao conectar ao banco de dados."); }

$serie_id = $_GET['id'] ?? null;
if (!$serie_id) { die("ID da série não fornecido."); }

$stmt = $pdo->prepare("SELECT * FROM series WHERE id = :id");
$stmt->execute([':id' => $serie_id]);
$serie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$serie) { die("Série não encontrada."); }

$categorias = $pdo->query("SELECT id, nome FROM categoria ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Série: <?= htmlspecialchars($serie['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .card { border: none; border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        /* --- CSS PARA O TEMA ESCURO --- */
        body.dark-mode { background-color: #121212; color: #e0e0e0; }
        body.dark-mode .card { background-color: #1e1e1e; border: 1px solid #2c2c2c; }
        body.dark-mode .card-header, body.dark-mode h4, body.dark-mode h5 { background-color: #1e1e1e !important; color: #ffffff; }
        body.dark-mode .form-control, body.dark-mode .form-select { background-color: #2a2a2a; color: #e0e0e0; border-color: #3c3c3c; }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="card">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-pencil-alt me-2"></i>Editar Série</h4>
                <a href="series.php" class="btn btn-secondary">Voltar para a Lista</a>
            </div>
            <div class="card-body p-4">
                <form id="editSerieForm">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($serie['id']) ?>">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome da Série</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($serie['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="plot" class="form-label">Sinopse</label>
                                <textarea class="form-control" id="plot" name="plot" rows="5"><?= htmlspecialchars($serie['plot']) ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Capa (Poster)</label>
                                <img id="posterPreview" src="<?= htmlspecialchars($serie['cover']) ?>" class="img-fluid rounded mb-2">
                                <input type="text" class="form-control" id="cover" name="cover" value="<?= htmlspecialchars($serie['cover']) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="release_date" class="form-label">Data de Lançamento</label>
                            <input type="date" class="form-control" id="release_date" name="release_date" value="<?= htmlspecialchars($serie['release_date']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Categoria</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>" <?= ($serie['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">Salvar Alterações</button>
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

// --- JAVASCRIPT PARA SALVAR O FORMULÁRIO ---
document.getElementById('editSerieForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = 'Salvando...';
    try {
        const response = await fetch('ajax/serie_atualizar.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            window.location.href = 'series.php';
        }
    } catch (error) {
        alert('Ocorreu um erro de comunicação.');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Salvar Alterações';
    }
});
</script>

</body>
</html>