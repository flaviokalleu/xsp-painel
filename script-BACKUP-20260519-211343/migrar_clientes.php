<?php
// 1. INCLUI O MENU E CABEÇALHO DO SEU PAINEL
// Isso garante que a página tenha o mesmo visual e seja responsiva.
require_once 'menu.php';

// =================================================================
// OBSERVAÇÃO: A lógica de checagem de perfil (admin/revendedor)
// foi REMOVIDA para garantir que TODAS as importações forcem o valor
// de conexões para 1, independentemente do nível de acesso do usuário.
// =================================================================
?>

<script>
// Não é mais necessário a variável global IS_ADMIN, pois a regra é a mesma para todos.
</script>

<style>
.page-title-box h4 {
    font-weight: 600;
    color: var(--text-primary);
}

.card {
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: none;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.card-title {
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-control {
    border-radius: 8px;
    padding: 12px;
}
.form-control:focus {
    box-shadow: 0 0 0 2px var(--sidebar-active-bg);
    border-color: var(--sidebar-active-bg);
}

.btn {
    border-radius: 8px;
    padding: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-primary {
    background: linear-gradient(45deg, #0d6efd, #3c82f6);
}
.btn-success {
    background: linear-gradient(45deg, #198754, #28a745);
}

#info_lista {
    border-left: 4px solid #0d6efd;
    background-color: var(--bg-card); /* Garante o fundo correto no modo escuro */
}

.info-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}
.info-item:last-child {
    border-bottom: none;
}

.info-item .label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.info-item .value {
    font-weight: 600;
    color: var(--text-primary);
    word-break: break-all;
    text-align: right;
}

.info-item.cost {
    background-color: #fff3cd;
    color: #664d03;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}
[data-theme="dark"] .info-item.cost {
    background-color: #3b320d;
    color: #ffc107;
}
.info-item.cost .value {
    color: inherit;
    font-size: 1.1rem;
}

#mensagem {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 24px;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0"><i class="fa-solid fa-people-arrows text-primary"></i> Migrar Clientes</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4"><i class="fa-solid fa-link"></i> Dados da Lista M3U</h5>

                    <div class="mb-3">
                        <label for="m3u_url" class="form-label">Cole o link da lista M3U do cliente:</label>
                        <input type="text" class="form-control" id="m3u_url" placeholder="http://servidor.com:porta/get.php?username=...">
                    </div>

                    <button id="btn_analisar" class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass"></i> Analisar Informações</button>

                    <div id="mensagem" class="mt-3 fw-bold text-center"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-md-12">
            <div class="card" id="info_lista" style="display: none;">
                <div class="card-body">
                    <h5 class="card-title mb-4"><i class="fa-solid fa-circle-info"></i> Informações da Lista</h5>

                    <div class="info-details">
                        <div class="info-item">
                            <span class="label"><i class="fa-solid fa-server"></i> Servidor:</span>
                            <strong id="res_servidor" class="value"></strong>
                        </div>
                        <div class="info-item">
                            <span class="label"><i class="fa-solid fa-user"></i> Usuário:</span>
                            <strong id="res_usuario" class="value"></strong>
                        </div>
                        <div class="info-item">
                            <span class="label"><i class="fa-solid fa-key"></i> Senha:</span>
                            <strong id="res_senha" class="value"></strong>
                        </div>
                        <div class="info-item">
                            <span class="label"><i class="fa-solid fa-tower-broadcast"></i> Conexões:</span>
                            <strong id="res_conexoes" class="value"></strong>
                        </div>
                        <div class="info-item">
                            <span class="label"><i class="fa-solid fa-calendar-days"></i> Dias Restantes:</span>
                            <strong id="res_dias" class="value"></strong>
                        </div>
                        <div class="info-item cost">
                            <span class="label"><i class="fa-solid fa-coins"></i> Custo de Importação:</span>
                            <strong id="res_custo" class="value"></strong>
                        </div>
                    </div>

                    <button id="btn_importar" class="btn btn-success w-100 mt-4"><i class="fa-solid fa-download"></i> Importar Cliente</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 2. INCLUI O RODAPÉ DO SEU PAINEL (se existir um)
// Se você tiver um arquivo como 'footer.php', pode incluí-lo aqui.
// require_once 'footer.php';
?>

<script>
$(document).ready(function(){

    let dadosClienteParaImportar = {};

    // --- AÇÃO DO BOTÃO ANALISAR ---
    $('#btn_analisar').click(function(){
        let m3u_url = $('#m3u_url').val();

        if(m3u_url === "") {
            Swal.fire('Atenção!', 'Por favor, insira um link M3U.', 'warning');
            return;
        }

        // Mostra uma mensagem de carregamento mais visual
        $('#btn_analisar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Analisando...');
        $('#info_lista').fadeOut();
        $('#mensagem').html('');


        $.ajax({
            url: 'api/api_migracao.php', // Verifique se o caminho para a API está correto
            type: 'POST',
            data: {
                action: 'analisar',
                url: m3u_url,
                token: (typeof SESSION_TOKEN !== 'undefined' ? SESSION_TOKEN : '')
            },
            dataType: 'json',
            success: function(response){
                if(response.status === 'success'){
                    // Preenche os campos na tela (mostra as conexões reais identificadas)
                    $('#res_servidor').text(response.data.servidor);
                    $('#res_usuario').text(response.data.usuario);
                    $('#res_senha').text(response.data.senha);
                    $('#res_conexoes').text(response.data.conexoes);
                    $('#res_dias').text(response.data.dias_restantes);
                    $('#res_custo').text(response.data.custo_importacao);

                    // Armazena os dados para importação (incluindo as conexões reais)
                    dadosClienteParaImportar = response.data;

                    // Mostra a caixa de informações com um efeito suave
                    $('#info_lista').fadeIn();
                } else {
                    Swal.fire('Erro na Análise!', response.message, 'error');
                }
            },
            error: function(){
                Swal.fire('Erro de Comunicação!', 'Não foi possível conectar à API. Verifique o console para mais detalhes.', 'error');
            },
            complete: function() {
                // Restaura o botão ao estado original
                $('#btn_analisar').prop('disabled', false).html('<i class="fa-solid fa-magnifying-glass"></i> Analisar Informações');
            }
        });
    });

    // --- AÇÃO DO BOTÃO IMPORTAR ---
    $('#btn_importar').click(function(){
        Swal.fire({
            title: 'Confirmar Importação',
            html: "Você está prestes a importar este cliente para o seu painel.<br>O custo em créditos será debitado da sua conta. Deseja continuar?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, importar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Desabilita o botão para evitar cliques duplos
                const originalButtonHtml = $(this).html();
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Importando...');

                // =================================================================
                // LÓGICA DE UNIFORMIZAÇÃO DE CONEXÕES
                // =================================================================
                /* * Força o campo de conexões para 1 para todas as importações,
                 * independentemente do perfil do usuário logado (Admin ou Revendedor).
                 */
                dadosClienteParaImportar.conexoes = 1;
                // =================================================================

                $.ajax({
                    url: 'api/api_migracao.php',
                    type: 'POST',
                    data: {
                        action: 'importar',
                        cliente: dadosClienteParaImportar,
                        token: (typeof SESSION_TOKEN !== 'undefined' ? SESSION_TOKEN : '')
                    },
                    dataType: 'json',
                    success: function(response){
                        if(response.status === 'success'){
                            Swal.fire('Sucesso!', response.message, 'success');
                            $('#info_lista').fadeOut();
                            $('#m3u_url').val(''); // Limpa o campo de URL

                            // Atualiza os créditos na tela, se a função 'get_credits' existir no seu custom.js
                            if (typeof get_credits === 'function') {
                                get_credits();
                            }
                        } else {
                            Swal.fire('Erro na Importação!', response.message, 'error');
                        }
                    },
                    error: function(){
                        Swal.fire('Erro de Comunicação!', 'Não foi possível importar o cliente.', 'error');
                    },
                    complete: function() {
                        // Restaura o botão de importação
                        $('#btn_importar').prop('disabled', false).html(originalButtonHtml);
                    }
                });
            }
        });
    });
});
</script>
