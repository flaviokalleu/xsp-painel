<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador M3U PRO com TMDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-white">

    <div class="container-fluid py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card bg-dark-2">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="http://51.161.72.178/img/logo.png" alt="Logo" style="width: 70px;">
                            <h2 class="mt-2">Importador M3U PRO com TMDB</h2>
                            <p class="text-muted">Importe e enriqueça seus conteúdos automaticamente.</p>
                        </div>

                        <div id="url-input-area">
                            <div class="mb-3">
                                <label for="m3u_url" class="form-label">Cole a URL da lista .m3u</label>
                                <input type="text" class="form-control" id="m3u_url" placeholder="http://exemplo.com/lista.m3u">
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary" id="analyze-btn">Analisar Categorias da Lista</button>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4 pt-3 border-top border-secondary">
                            <a href="/dashboard.php" class="btn btn-success" style="background-color: #198754; border-color: #198754;">
                                <i class="bi bi-house-door-fill"></i> Voltar ao Dashboard
                            </a>
                        </div>
                        <div id="category-selection-area" style="display: none;">
                            <h4 class="mb-3">Selecione as Categorias para Importar</h4>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="select-all-cats">
                                <label class="form-check-label" for="select-all-cats">
                                    <strong>Selecionar Todas as Categorias</strong>
                                </label>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <h5><i class="bi bi-broadcast"></i> Canais</h5>
                                    <div id="live-categories" class="category-list"></div>
                                </div>
                                <div class="col-md-4">
                                    <h5><i class="bi bi-film"></i> Filmes</h5>
                                    <div id="movie-categories" class="category-list"></div>
                                </div>
                                <div class="col-md-4">
                                    <h5><i class="bi bi-collection-play"></i> Séries</h5>
                                    <div id="series-categories" class="category-list"></div>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-4">
                                <button class="btn btn-success" id="import-btn">Iniciar Importação das Categorias Selecionadas</button>
                            </div>
                        </div>

                        <div id="processing-area" class="mt-4" style="display: none;">
                             <div class="progress" style="height: 25px;">
                                 <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                             </div>
                             <div id="log-console" class="log-console mt-3"></div>
                             <div class="mt-3">
                                 <h5>Resumo da Importação:</h5>
                                 <p><i class="bi bi-broadcast text-info"></i> Canais Adicionados: <span id="live-count">0</span></p>
                                 <p><i class="bi bi-film text-warning"></i> Filmes Adicionados: <span id="movie-count">0</span></p>
                                 <p><i class="bi bi-collection-play text-danger"></i> Séries Adicionadas: <span id="series-count">0</span></p>
                             </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const analyzeBtn = document.getElementById('analyze-btn');
        const importBtn = document.getElementById('import-btn');
        const m3uUrlInput = document.getElementById('m3u_url');
        const urlInputArea = document.getElementById('url-input-area');
        const categorySelectionArea = document.getElementById('category-selection-area');
        const processingArea = document.getElementById('processing-area');
        const logConsole = document.getElementById('log-console');
        const progressBar = document.getElementById('progress-bar');
        const selectAllCheckbox = document.getElementById('select-all-cats');

        let totalItems = 0;
        let processedItems = 0;
        let taskId = '';
        let cacheId = '';

        function log(message, type = 'info') {
            const time = new Date().toLocaleTimeString();
            logConsole.innerHTML += `<div class="log-${type}">[${time}] ${message}</div>`;
            logConsole.scrollTop = logConsole.scrollHeight;
        }

        analyzeBtn.addEventListener('click', async function () {
            const url = m3uUrlInput.value;
            if (!url) { alert('Por favor, insira uma URL.'); return; }
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Analisando...';
            log('Iniciando análise da lista...');
            const formData = new FormData();
            formData.append('m3u_url', url);
            try {
                const response = await fetch('processar.php?step=analyze', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    log('Análise concluída. Mostrando categorias.');
                    cacheId = data.cache_id || '';
                    displayCategories(data.categories);
                    urlInputArea.style.display = 'none';
                    categorySelectionArea.style.display = 'block';
                } else {
                    log(`ERRO NA ANÁLISE: ${data.message}`, 'error');
                    this.disabled = false;
                    this.innerHTML = 'Analisar Categorias da Lista';
                }
            } catch (error) {
                log(`ERRO FATAL: ${error}`, 'error');
                this.disabled = false;
                this.innerHTML = 'Analisar Categorias da Lista';
            }
        });

        function displayCategories(categories) {
            const createCheckboxHTML = (cat) => `
                <div class="form-check">
                    <input class="form-check-input cat-checkbox" type="checkbox" value="${cat}" id="cat-${cat.replace(/[^a-zA-Z0-9]/g, "")}">
                    <label class="form-check-label" for="cat-${cat.replace(/[^a-zA-Z0-9]/g, "")}">${cat}</label>
                </div>`;
            document.getElementById('live-categories').innerHTML = categories.live.map(createCheckboxHTML).join('');
            document.getElementById('movie-categories').innerHTML = categories.movie.map(createCheckboxHTML).join('');
            document.getElementById('series-categories').innerHTML = categories.series.map(createCheckboxHTML).join('');
        }

        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.cat-checkbox').forEach(checkbox => { checkbox.checked = this.checked; });
        });

        importBtn.addEventListener('click', async function() {
            const selectedCategories = Array.from(document.querySelectorAll('.cat-checkbox:checked')).map(cb => cb.value);
            if (selectedCategories.length === 0) { alert('Por favor, selecione pelo menos uma categoria.'); return; }
            
            categorySelectionArea.style.display = 'none';
            processingArea.style.display = 'block';
            log('Preparando lista com as categorias selecionadas...');
            
            const formData = new FormData();
            formData.append('m3u_url', m3uUrlInput.value);
            formData.append('selected_categories', JSON.stringify(selectedCategories));
            formData.append('cache_id', cacheId);
            
            try {
                const response = await fetch('processar.php?step=prepare', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    totalItems = data.totalItems;
                    taskId = data.task_id;
                    log(`Lista filtrada. Total de ${totalItems} itens para importar.`);
                    if (totalItems > 0) {
                        processInChunks();
                    } else {
                        log('Nenhum item para importar. Processo finalizado.', 'success');
                        updateProgressBar(100);
                    }
                } else {
                    log(`ERRO NA PREPARAÇÃO: ${data.message}`, 'error');
                }
            } catch (error) {
                log(`ERRO FATAL NA PREPARAÇÃO: ${error}`, 'error');
            }
        });

        async function processInChunks() {
            while (processedItems < totalItems) {
                try {
                    const response = await fetch(`processar.php?step=process&task_id=${taskId}&offset=${processedItems}`);
                    const data = await response.json();
                    if (data.success) {
                        data.log.forEach(entry => log(entry.message, entry.type));
                        processedItems += data.processed_count;
                        updateSummary(data.added_names);
                        updateProgressBar((processedItems / totalItems) * 100);
                    } else {
                        log(`Erro no lote: ${data.message}`, 'error');
                        break; 
                    }
                } catch (error) {
                    log(`ERRO FATAL NO PROCESSAMENTO: ${error}`, 'error');
                    break;
                }
            }

            if (processedItems >= totalItems) {
                log('Importação concluída com sucesso!', 'success');
            } else {
                log('Importação interrompida.', 'error');
            }
        }

        function updateProgressBar(percentage) {
            const percent = Math.min(Math.round(percentage), 100);
            progressBar.style.width = percent + '%';
            progressBar.innerText = percent + '%';
        }

        function updateSummary(added) {
            document.getElementById('live-count').innerText = parseInt(document.getElementById('live-count').innerText) + (added.live?.length || 0);
            document.getElementById('movie-count').innerText = parseInt(document.getElementById('movie-count').innerText) + (added.movie?.length || 0);
            document.getElementById('series-count').innerText = parseInt(document.getElementById('series-count').innerText) + (added.series?.length || 0);
        }
    });
    </script>

</body>
</html>