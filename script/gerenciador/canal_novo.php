<?php
// ARQUIVO: /gerenciador/canal_novo.php (VERSÃO FINAL COMPLETA)
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
    <title>Adicionar Novo Canal</title>
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
                <h4 class="mb-0"><i class="fas fa-plus me-2"></i>Adicionar Novo Canal</h4>
                <a href="canais.php" class="btn btn-secondary">Voltar para a Lista</a>
            </div>
            <div class="card-body p-4">
                <form id="addCanalForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nome do Canal</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Categoria</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="" selected disabled>Selecione uma categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="stream_icon" class="form-label">URL do Logo</label>
                        <input type="text" class="form-control" id="stream_icon" name="stream_icon">
                    </div>
                    <div class="mb-3">
                        <label for="epg_channel_id" class="form-label">ID do EPG (Opcional)</label>
                        <input type="text" class="form-control" id="epg_channel_id" name="epg_channel_id">
                    </div>
                    <div class="mb-3">
                        <label for="link" class="form-label">URL do Stream</label>
                        <input type="text" class="form-control" id="link" name="link" required>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success btn-lg">Salvar Canal</button>
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
document.getElementById('addCanalForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = 'Salvando...';

    try {
        const response = await fetch('ajax/canal_salvar.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            form.reset();
        }
    } catch (error) {
        alert('Ocorreu um erro de comunicação ao tentar salvar o canal.');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Salvar Canal';
    }
});
</script>

</body>
</html>