<?php
session_start();
// É crucial que a conexão com o banco de dados e as funções de busca sejam carregadas
require_once('./api/controles/db.php');
require_once('./api/controles/dashboard.php'); // Onde estão as funções Dashboard(), testes(), etc.
require_once("menu.php");

// Executa as funções para buscar os dados que serão exibidos na carga inicial
$dadosAtivos = Dashboard();
$dadosTestes = testes();
$conteudos = conteudos();
$dadosVencimentos = getDadosVencimentos(7); // Busca dados para os próximos 7 dias
?>

<style>
    /* Estilos para cards coloridos (mantidos do seu código original) */
    .color-card { color: #ffffff !important; }
    .color-card .card-label, .color-card .icon-container { color: rgba(255, 255, 255, 0.9) !important; }
    .color-card .card-value { color: #ffffff !important; }
    .card-orange-red { background: linear-gradient(4deg, #304b73, #304b73); }
    .card-dark-green { background: linear-gradient(4deg, #0a9683, #0a9683); }
    .card-red-purple { background: linear-gradient(4deg, #8c0303, #8c0303); }
    .card-lilac-silver { background: linear-gradient(4deg, #038c0e, #038c0e); }

    /* CSS para os gráficos de recursos do servidor */
    .resource-card .card-body { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem 1rem; }
    .circular-progress { position: relative; width: 140px; height: 140px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .circular-progress::before { content: ""; position: absolute; height: 80%; width: 80%; background-color: var(--card-bg-color, #c9fff8); border-radius: 50%; }
    .progress-value { position: relative; font-size: 2.2rem; font-weight: 500; color: #5b7078; }
    .card-body h6 { font-weight: 500; color: #555 !important; }
    #cpu-progress { background: conic-gradient(#e74c3c calc(var(--value, 0) * 3.6deg), #f0f0f0 0deg); }
    #ram-progress { background: conic-gradient(#2ecc71 calc(var(--value, 0) * 3.6deg), #f0f0f0 0deg); }
    #disk-progress { background: conic-gradient(#e74c3c calc(var(--value, 0) * 3.6deg), #f0f0f0 0deg); }

    /* =================================================================== */
    /* CORES PARA OS ÍCONES DOS CARDS DE CLIENTES                        */
    /* =================================================================== */
    .fa-users { color: #3498db !important; } /* Azul */
    .fa-users-slash { color: #f1c40f !important; } /* Amarelo */
    .summary-card.card-green .icon i.fa-dollar-sign { color: #fff !important; }
    .card:not(.summary-card) .icon-container i.fa-dollar-sign { color: #555 !important; }
    .fa-vial { color: #9b59b6 !important; } /* Roxo */
    .fa-user-plus { color: #1abc9c !important; } /* Verde-água */
    .fa-user-xmark { color: #e74c3c !important; } /* Vermelho */
    
    /* =================================================================== */
    /* CSS ATUALIZADO E INTELIGENTE PARA A SEÇÃO DE VENCIMENTOS         */
    /* =================================================================== */
    
    /* --- TEMA CLARO (PADRÃO) --- */
    .vencimentos-wrapper { background-color: #ececec; padding: 20px; border-radius: 12px; margin-top: 2rem; }
    .vencimentos-wrapper .page-header { background-color: #053f82; color: #fff000; padding: 10px 15px; border-radius: 8px; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    .vencimentos-wrapper .page-header h4 { margin-bottom: 0; color: #ffffff; font-size: 1.2rem; font-weight: 600; }
    .vencimentos-wrapper .page-header .header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .vencimentos-wrapper .page-header .form-select,
    .vencimentos-wrapper .page-header .btn-primary { background-color: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.3); color: #ffffff; }
    .vencimentos-wrapper .page-header .form-select option { color: #000; }
    .vencimentos-wrapper .card { background-color: #fff!important; border: 1px solid #e9ecef !important; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); }
    .vencimentos-wrapper .summary-card { padding: 20px; display: flex; align-items: center; gap: 20px; margin-bottom: 1rem; border-radius: 12px !important; }
    .vencimentos-wrapper .summary-card .icon { width: 60px; height: 60px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #fff; }
    .vencimentos-wrapper .summary-card.card-red .icon { background-color: #dc3545; }
    .vencimentos-wrapper .summary-card.card-blue .icon { background-color: #022559; }
    .vencimentos-wrapper .summary-card.card-green .icon { background-color: #198754; }
    .vencimentos-wrapper .summary-card .info .value { font-size: 2.2rem; font-weight: 700; color: #212529 !important; line-height: 1.2; }
    .vencimentos-wrapper .summary-card .info .label { font-size: 0.9rem; color: #6c757d !important; }
    .vencimentos-wrapper .table { color: #212529 !important; }
    .vencimentos-wrapper .table th { font-weight: 600; color: #022559; text-transform: uppercase; font-size: 0.8rem; background-color: #d6d6d6 !important; border-bottom: 1px solid #dee2e6 !important; }
    
    .vencimentos-wrapper .table thead th {
        background-color: #a00606 !important;
        color: #ffffff !important;
        border-bottom: none !important;
    }
    
    .vencimentos-wrapper .table td { vertical-align: middle; }
    .vencimentos-wrapper .status-badge { display: inline-block; padding: 0.35em 0.65em; font-size: .75em; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 50rem; }
    .vencimentos-wrapper .status-bloqueado { color: #842029; background-color: #fff000; }
    .vencimentos-wrapper .btn i { margin-right: 5px; }
    .vencimentos-wrapper .alert-warning { color: #664d03; background-color: #fff3cd; border-color: #ffecb5; }

    /* --- TEMA ESCURO --- */
    [data-theme="dark"] .vencimentos-wrapper { background-color: #212529; }
    [data-theme="dark"] .vencimentos-wrapper .page-header .form-select,
    [data-theme="dark"] .vencimentos-wrapper .page-header .btn-primary { background-color: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.2); }
    [data-theme="dark"] .vencimentos-wrapper .page-header .form-select option { background-color: #343a40; color: #d6d6d6; }
    [data-theme="dark"] .vencimentos-wrapper .card { background-color: #2c2c2c !important; border-color: #404040 !important; }
    [data-theme="dark"] .vencimentos-wrapper .summary-card .info .value { color: #d6d6d6 !important; }
    [data-theme="dark"] .vencimentos-wrapper .summary-card .info .label { color: #adb5bd !important; }
    [data-theme="dark"] .vencimentos-wrapper .table { color: #dee2e6 !important; }
    [data-theme="dark"] .vencimentos-wrapper .table tbody { background-color: #2c2c2c !important; }
    [data-theme="dark"] .vencimentos-wrapper .table tbody tr { border-color: #495057 !important; }
    [data-theme="dark"] .vencimentos-wrapper .table-hover tbody tr:hover { background-color: rgba(255, 255, 255, 0.075) !important; color: #d6d6d6 !important; }
    [data-theme="dark"] .vencimentos-wrapper .alert-warning { color: #ffc107; background-color: #332701; border-color: #4d3c0d; }
    [data-theme="dark"] .vencimentos-wrapper .text-muted { color: #adb5bd !important; }
    [data-theme="dark"] .vencimentos-wrapper .text-danger { color: #ff8a80 !important; }
    [data-theme="dark"] .vencimentos-wrapper .text-success { color: #b9f6ca !important; }
    
    /* CSS PARA O RODAPÉ (Somente o básico de rodapé foi mantido, estilos de botão/modal removidos) */
    .version-footer { text-align: center; padding: 30px 15px; margin-top: 40px; background-color: #02324a; color: #555; font-size: 0.9rem; }
    .version-footer .copyright { margin-top: 15px; font-size: 0.8rem; color: #777; }
    [data-theme="dark"] .version-footer { background-color: #2c2c2c; color: #aaa; }
    [data-theme="dark"] .version-footer .copyright { color: #888; }
    
</style>

<div class="p-3 p-md-4">
    <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 1): ?>
    <div class="row">
        <div class="col-12"><h4 class="section-title">Resumo do Conteúdo</h4></div>
        <div class="col-sm-6 col-lg-3 mb-4"><div class="card color-card card-orange-red h-100"><div class="card-body"><div class="icon-container"><i class="fa-solid fa-tv"></i></div><div class="card-text-content"><div class="card-label">Canais</div><p class="card-value"><?php echo $conteudos['TotalLiveStreams']; ?></p></div></div></div></div>
        <div class="col-sm-6 col-lg-3 mb-4"><div class="card color-card card-dark-green h-100"><div class="card-body"><div class="icon-container"><i class="fa-solid fa-film"></i></div><div class="card-text-content"><div class="card-label">Filmes</div><p class="card-value"><?php echo $conteudos['TotalMovieStreams']; ?></p></div></div></div></div>
        <div class="col-sm-6 col-lg-3 mb-4"><div class="card color-card card-red-purple h-100"><div class="card-body"><div class="icon-container"><i class="fa-solid fa-clapperboard"></i></div><div class="card-text-content"><div class="card-label">Séries</div><p class="card-value"><?php echo $conteudos['TotalSeries']; ?></p></div></div></div></div>
        <div class="col-sm-6 col-lg-3 mb-4"><div class="card color-card card-lilac-silver h-100"><div class="card-body"><div class="icon-container"><i class="fa-solid fa-photo-film"></i></div><div class="card-text-content"><div class="card-label">Episódios</div><p class="card-value"><?php echo $conteudos['TotalEpisodes']; ?></p></div></div></div></div>
    </div>
    <?php endif; ?>
    <div class="row mt-3">
        <div class="col-12"><h4 class="section-title">Resumo de Clientes</h4></div>
        <div class="col-md-6 col-lg-4 mb-4"><div class="card h-100"><div class="card-body"><div class="icon-container"><i class="fa-solid fa-users"></i></div><div class="card-text-content"><div class="card-label">Clientes Ativos</div><p class="card-value"><?php echo $dadosAtivos['clientesAtivos']; ?></p></div></div></div></div>
        <div class="col-md-6 col-lg-4 mb-4"><div class="card h-100"><div class="card-body"><div class="icon-container"><i class="fa-solid fa-users-slash"></i></div><div class="card-text-content"><div class="card-label">Total Vendidos</div><p class="card-value"><?php echo $dadosAtivos['clientesvencidostotal']; ?></p></div></div></div></div>
        <div class="col-md-6 col-lg-4 mb-4"><div class="card h-100"><div class="card-body"><div class="icon-container"><i class="fa-solid fa-dollar-sign"></i></div><div class="card-text-content"><div class="card-label">Valores a Receber</div><p class="card-value">R$ <?php echo number_format($dadosAtivos['clientesarenovar_valor'], 2, ',', '.'); ?></p></div></div></div></div>
        <div class="col-md-6 col-lg-4 mb-4"><div class="card h-100"><div class="card-body"><div class="icon-container"><i class="fa-solid fa-vial"></i></div><div class="card-text-content"><div class="card-label">Testes Ativos</div><p class="card-value"><?php echo $dadosTestes['TestesAtivos']; ?></p></div></div></div></div>
        <div class="col-md-6 col-lg-4 mb-4"><div class="card h-100"><div class="card-body"><div class="icon-container"><i class="fa-solid fa-user-plus"></i></div><div class="card-text-content"><div class="card-label">Novos (Mês)</div><p class="card-value"><?php echo $dadosAtivos['clientesnovos']; ?></p></div></div></div></div>
        <div class="col-md-6 col-lg-4 mb-4"><div class="card h-100"><div class="card-body"><div class="icon-container"><i class="fa-solid fa-user-xmark"></i></div><div class="card-text-content"><div class="card-label">Vencidos (Mês)</div><p class="card-value"><?php echo $dadosAtivos['clientesvencidos_este_mes']; ?></p></div></div></div></div>
    </div>
    <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 1): ?>
    <div class="row mt-4">
        <div class="col-12"><h4 class="section-title">Recursos do Servidor</h4></div>
        <div class="col-md-4 mb-4"><div class="card resource-card h-100"><div class="card-body text-center"><div class="circular-progress" id="cpu-progress"><span class="progress-value">0%</span></div><h6 class="mt-3 mb-0">Utilização de CPU</h6></div></div></div>
        <div class="col-md-4 mb-4"><div class="card resource-card h-100"><div class="card-body text-center"><div class="circular-progress" id="ram-progress"><span class="progress-value">0%</span></div><h6 class="mt-3 mb-0">Utilização de RAM</h6></div></div></div>
        <div class="col-md-4 mb-4"><div class="card resource-card h-100"><div class="card-body text-center"><div class="circular-progress" id="disk-progress"><span class="progress-value">0%</span></div><h6 class="mt-3 mb-0">Uso do Disco '/'</h6></div></div></div>
    </div>
    <?php endif; ?>
</div>

<div class="vencimentos-wrapper">
    <div class="page-header">
        <h4><i class="fas fa-calendar-alt me-2"></i>Vencimento dos clientes</h4>
        <div class="header-actions">
            <select id="refresh-interval" class="form-select form-select-sm" style="width: auto;">
                <option value="30">30 segundos</option><option value="60">1 minuto</option><option value="300">5 minutos</option><option value="0" selected>Nunca</option>
            </select>
            <button id="update-btn" class="btn btn-sm btn-primary">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                <i class="fas fa-sync-alt"></i>
                <span class="ms-1">Atualizar</span>
            </button>
        </div>
    </div>
    <div class="alert alert-warning" role="alert"><i class="fas fa-exclamation-triangle me-2"></i>Vencendo nos próximos dias</div>
    <div class="row">
        <div class="col-lg-4 col-md-6"><div class="card summary-card card-red"><div class="icon"><i class="fas fa-times"></i></div><div class="info"><div class="value" id="nao-renovados-count"><?php echo $dadosVencimentos['nao_renovados_count']; ?></div><div class="label">Não renovado (Atrasado)</div></div></div></div>
        <div class="col-lg-4 col-md-6"><div class="card summary-card card-blue"><div class="icon"><i class="fas fa-exclamation"></i></div><div class="info"><div class="value" id="proximos-vencimentos-count"><?php echo $dadosVencimentos['proximos_vencimentos_count']; ?></div><div class="label">Próximos vencimentos</div></div></div></div>
        <div class="col-lg-4 col-md-12"><div class="card summary-card card-green"><div class="icon"><i class="fas fa-dollar-sign"></i></div><div class="info"><div class="value" id="valor-total-receber">R$ <?php echo number_format($dadosVencimentos['valor_total_a_receber'], 2, ',', '.'); ?></div><div class="label">Valor total a receber</div></div></div></div>
    </div>
    <div class="card mt-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-3">NOME DO CLIENTE</th><th>TELEFONE</th><th>VENCIMENTO</th><th>STATUS</th><th>VALOR</th><th class="text-end px-3">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-vencimentos-body">
                        <tr><td colspan="6" class="text-center text-muted py-4">A carregar dados dos clientes...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="version-footer">
    <p>Versão atual : 5.2.34 <span id="current-version-text">...</span></p>
    <p class="copyright">© AGS PLAY | Todos os direitos reservados.</p>
</div>

<div class="modal fade" id="modal_master" tabindex="-1" aria-labelledby="modal_master_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-primary text-white rounded-top-4" id="modal_master_header">
                <h5 class="modal-title" id="modal_master-titulo"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="modal_master_form" onsubmit="event.preventDefault();" autocomplete="off">
                <div id="modal_master-body" class="modal-body p-4 overflow-auto" style="max-height: 70vh;"></div>
                <div id="modal_master-footer" class="modal-footer border-top-0"></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // Script para os gráficos de recursos do servidor (sem alterações)
    const cpuProgress = document.getElementById('cpu-progress');
    const ramProgress = document.getElementById('ram-progress');
    const diskProgress = document.getElementById('disk-progress');
    if (cpuProgress && ramProgress && diskProgress) {
        const cpuValue = cpuProgress.querySelector('.progress-value');
        const ramValue = ramProgress.querySelector('.progress-value');
        const diskValue = diskProgress.querySelector('.progress-value');
        function updateServerStats() {
            fetch('api/get_server_stats.php')
                .then(response => response.json())
                .then(data => {
                    cpuValue.textContent = data.cpu + '%'; cpuProgress.style.setProperty('--value', data.cpu);
                    ramValue.textContent = data.ram + '%'; ramProgress.style.setProperty('--value', data.ram);
                    diskValue.textContent = data.disk + '%'; diskProgress.style.setProperty('--value', data.disk);
                })
                .catch(error => { console.error('Erro ao buscar estatísticas do servidor:', error); cpuValue.textContent = 'N/A'; ramValue.textContent = 'N/A'; diskValue.textContent = 'N/A'; });
        }
        updateServerStats(); setInterval(updateServerStats, 5000); 
    }

    // =================================================================== //
    // SCRIPT FINAL E CORRIGIDO PARA A SEÇÃO DE VENCIMENTOS
    // =================================================================== //
    const intervalSelect = document.getElementById('refresh-interval');
    const updateBtn = document.getElementById('update-btn');
    const tabelaBody = document.getElementById('tabela-vencimentos-body');
    let refreshTimer = null;

    async function updateVencimentosSection() {
        const spinner = updateBtn.querySelector('.spinner-border');
        const btnContent = updateBtn.querySelector('span.ms-1');
        const btnIcon = updateBtn.querySelector('i.fa-sync-alt');

        spinner.classList.remove('d-none');
        if(btnContent) btnContent.classList.add('d-none');
        if(btnIcon) btnIcon.classList.add('d-none');
        updateBtn.disabled = true;

        try {
            const response = await fetch('api/get_vencimentos_data.php');
            if (!response.ok) { throw new Error(`Erro na rede: ${response.statusText}`); }
            const data = await response.json();

            document.getElementById('nao-renovados-count').textContent = data.nao_renovados_count;
            document.getElementById('proximos-vencimentos-count').textContent = data.proximos_vencimentos_count;
            document.getElementById('valor-total-receber').textContent = `R$ ${parseFloat(data.valor_total_a_receber).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            tabelaBody.innerHTML = ''; 

            if (data.lista_vencidos && data.lista_vencidos.length > 0) {
                data.lista_vencidos.forEach(cliente => {
                    const nomeCliente = cliente.name || cliente.usuario;
                    const telefoneLimpo = '55' + (cliente.telefone || '').replace(/\D/g, '');
                    const valorTotal = parseFloat(cliente.V_total).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const vencimentoFormatado = new Date(cliente.Vencimento.replace(/-/g, '\/')).toLocaleDateString('pt-BR');

                    let statusHtml = '';
                    if (cliente.status === 'Atrasado') {
                        statusHtml = `
                            <td>
                                ${vencimentoFormatado}
                                <small class="d-block text-danger">(atrasado ${cliente.dias_atrasado} dias)</small>
                            </td>
                            <td><span class="status-badge status-bloqueado">Bloqueado (${cliente.dias_atrasado} dias)</span></td>
                        `;
                    } else {
                        let textoDias = cliente.dias_para_vencer === 0 ? 'Vence hoje' : (cliente.dias_para_vencer === 1 ? 'Vence amanhã' : `Vence em ${cliente.dias_para_vencer} dias`);
                        statusHtml = `
                            <td>
                                ${vencimentoFormatado}
                                <small class="d-block text-success">(${textoDias})</small>
                            </td>
                            <td><span class="status-badge" style="background-color: #d1ecf1; color: #0c5460;">Ativo</span></td>
                        `;
                    }
                    
                    const rowHTML = `
                        <tr>
                            <td class="px-3">${escapeHTML(nomeCliente)}</td>
                            <td>${escapeHTML(cliente.telefone)}</td>
                            ${statusHtml}
                            <td>R$ ${valorTotal}</td>
                            <td class="text-end px-3">
                                <a href="https://api.whatsapp.com/send?phone=${telefoneLimpo}&text=Olá, ${encodeURIComponent(nomeCliente)}!" target="_blank" class="btn btn-sm btn-outline-primary me-2"><i class="fab fa-whatsapp"></i> Contato</a>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="modal_master('api/clientes.php', 'renovar_cliente', '${cliente.id}', 'usuario', '${cliente.usuario}')"><i class="fas fa-sync-alt"></i> Renovar</button>
                            </td>
                        </tr>
                    `;
                    tabelaBody.innerHTML += rowHTML;
                });
            } else {
                tabelaBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Nenhum cliente vencendo ou vencido nos próximos 7 dias.</td></tr>';
            }

        } catch (error) {
            console.error('Erro ao atualizar dados de vencimentos:', error);
            tabelaBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Falha ao carregar os dados. Tente novamente.</td></tr>';
        } finally {
            spinner.classList.add('d-none');
            if(btnContent) btnContent.classList.remove('d-none');
            if(btnIcon) btnIcon.classList.remove('d-none');
            updateBtn.disabled = false;
        }
    }

    function escapeHTML(str) {
        if (!str) return '';
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }
    
    function startAutoRefresh(intervalSeconds) {
        if (refreshTimer) { clearInterval(refreshTimer); }
        if (intervalSeconds > 0) { 
            refreshTimer = setInterval(updateVencimentosSection, intervalSeconds * 1000); 
        }
    }
    
    if(updateBtn && intervalSelect) {
        updateBtn.addEventListener('click', updateVencimentosSection);
        intervalSelect.addEventListener('change', function() { 
            startAutoRefresh(parseInt(this.value, 10)); 
        });
        startAutoRefresh(parseInt(intervalSelect.value, 10));
        updateVencimentosSection();
    }
    
    // O SCRIPT PARA VERIFICAÇÃO DE VERSÃO FOI REMOVIDO AQUI
    // O SCRIPT PARA CARREGAR O CHANGELOG NO MODAL FOI REMOVIDO AQUI
});
</script>