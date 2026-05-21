<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizador de Metadados TMDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/cyborg/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-console { background-color: #000; font-family: 'Courier New', monospace; height: 50vh; overflow-y: scroll; border: 1px solid #444; font-size: 0.9em; }
        .log-success { color: #28a745; }
        .log-error { color: #dc3545; }
        .log-info { color: #0dcaf0; }
        .log-dim { color: #6c757d; }
    </style>
</head>
<body>
    <div class="container my-4">
        <header class="text-center mb-4">
            <h1><i class="fas fa-sync-alt"></i> Atualizador de Metadados TMDB</h1>
            <p class="lead">Sistema para buscar e atualizar capas e sinopses de filmes e séries.</p>
        </header>

        <div class="d-grid mb-4">
            <a href="/dashboard.php" class="btn btn-success btn-lg" style="background-color: #28a745; border-color: #28a745;">
                <i class="fas fa-home"></i> Voltar ao Início
            </a>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Filmes</h5>
                        <p class="card-text">Busca e atualiza metadados para todos os filmes sem TMDB ID.</p>
                        <button class="btn btn-primary btn-lg" id="btn-movie" onclick="startProcess('movie')">Iniciar Atualização de Filmes</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Séries</h5>
                        <p class="card-text">Busca e atualiza metadados para todas as séries sem TMDB ID.</p>
                        <button class="btn btn-info btn-lg" id="btn-tv" onclick="startProcess('tv')">Iniciar Atualização de Séries</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="progress mt-4" style="height: 25px; display: none;" id="progress-container">
            <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                Console de Log
            </div>
            <div class="card-body p-2">
                <div id="log-console" class="log-console p-2 rounded"></div>
            </div>
        </div>
        
        </div>

<script>
    const logConsole = document.getElementById('log-console');
    const progressBar = document.getElementById('progress-bar');
    const progressContainer = document.getElementById('progress-container');

    function log(message, type = 'dim') {
        logConsole.innerHTML += `<div class="log-${type}">${new Date().toLocaleTimeString()}: ${message}</div>`;
        logConsole.scrollTop = logConsole.scrollHeight;
    }

    function updateProgress(current, total) {
        const percentage = total > 0 ? Math.round((current / total) * 100) : 0;
        progressBar.style.width = `${percentage}%`;
        progressBar.innerText = `${percentage}% (${current}/${total})`;
    }

    async function startProcess(type) {
        document.getElementById('btn-movie').disabled = true;
        document.getElementById('btn-tv').disabled = true;
        progressContainer.style.display = 'block';

        log(`🚀 Iniciando busca por ${type === 'movie' ? 'filmes' : 'séries'} para atualizar...`, 'info');

        try {
            const response = await fetch(`api.php?action=get_items_to_update&type=${type}`);
            const items = await response.json();

            if (items.length === 0) {
                log(`✅ Nenhum item novo para atualizar. Tudo em dia!`, 'success');
                resetUI();
                return;
            }

            log(`🔎 Encontrados ${items.length} itens para processar.`, 'info');
            updateProgress(0, items.length);

            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                log(`🔄 Processando ${i + 1}/${items.length}: ${item.name}`);

                const formData = new FormData();
                formData.append('id', item.id);
                formData.append('name', item.name);

                const updateResponse = await fetch(`api.php?action=update_item&type=${type}`, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await updateResponse.json();

                if (result.success) {
                    log(`✔️ ${item.name} -> ${result.message}`, 'success');
                } else {
                    log(`❌ ${item.name} -> ${result.message}`, 'error');
                }
                
                updateProgress(i + 1, items.length);
                await new Promise(resolve => setTimeout(resolve, 300)); // Pequeno delay para não sobrecarregar a API
            }

            log(`🎉 Processo concluído!`, 'success');

        } catch (error) {
            log(`🔥 ERRO FATAL: ${error.message}`, 'error');
        } finally {
            resetUI();
        }
    }

    function resetUI() {
        document.getElementById('btn-movie').disabled = false;
        document.getElementById('btn-tv').disabled = false;
    }
</script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> </body>
</html>