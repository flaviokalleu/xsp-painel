<?php
// 1. INCLUI O MENU E CABEÇALHO DO SEU PAINEL
// Isso garante que a página tenha o mesmo visual e seja responsiva.
require_once 'menu.php';
?>

<!-- O conteúdo da página começa aqui, dentro da tag <main> que já foi aberta pelo menu.php -->

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Migrar Clientes</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Dados da Lista M3U</h5>
                    
                    <div class="mb-3">
                        <label for="m3u_url" class="form-label">Cole o link da lista M3U do cliente:</label>
                        <input type="text" class="form-control" id="m3u_url" placeholder="http://servidor.com:porta/get.php?username=...">
                    </div>
                    
                    <button id="btn_analisar" class="btn btn-primary w-100">Analisar Informações</button>

                    <div id="mensagem" class="mt-3 fw-bold text-center"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-md-12">
            <!-- A caixa de informações agora usa o estilo de card do painel -->
            <div class="card info-box" id="info_lista" style="display: none; border-left: 3px solid #0d6efd;">
                <div class="card-body">
                    <h5 class="card-title mb-3">Informações da Lista</h5>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Servidor: <strong id="res_servidor" class="text-end"></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Usuário: <strong id="res_usuario"></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Senha: <strong id="res_senha"></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Conexões: <strong id="res_conexoes"></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Dias Restantes: <strong id="res_dias"></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Custo de Importação: <strong id="res_custo" class="text-warning"></strong>
                        </li>
                    </ul>

                    <button id="btn_importar" class="btn btn-success w-100 mt-3">Importar Cliente</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fim do conteúdo da página -->

<?php
// 2. INCLUI O RODAPÉ DO SEU PAINEL (se existir um)
// Se você tiver um arquivo como 'footer.php', pode incluí-lo aqui.
// require_once 'footer.php';
?>

<!-- O JavaScript permanece o mesmo, mas agora ele vai rodar dentro do ambiente do painel -->
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

        // Mostra uma mensagem de carregamento
        $('#mensagem').html('<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Analisando, aguarde...').css('color', '');
        $('#info_lista').fadeOut();

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
                    $('#mensagem').text("");

                    // Preenche os campos na tela
                    $('#res_servidor').text(response.data.servidor);
                    $('#res_usuario').text(response.data.usuario);
                    $('#res_senha').text(response.data.senha);
                    $('#res_conexoes').text(response.data.conexoes);
                    $('#res_dias').text(response.data.dias_restantes);
                    $('#res_custo').text(response.data.custo_importacao);

                    dadosClienteParaImportar = response.data;

                    // Mostra a caixa de informações com um efeito suave
                    $('#info_lista').fadeIn();
                } else {
                    $('#mensagem').text(response.message).css('color', 'red');
                }
            },
            error: function(){
                $('#mensagem').text("Erro de comunicação. Verifique o console.").css('color', 'red');
            }
        });
    });

    // --- AÇÃO DO BOTÃO IMPORTAR ---
    $('#btn_importar').click(function(){
        Swal.fire({
            title: 'Confirmar Importação',
            text: "Tem certeza que deseja importar este cliente?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, importar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#mensagem').html('<div class="spinner-border spinner-border-sm text-success" role="status"></div> Importando cliente...').css('color', '');
                
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
                        } else {
                            Swal.fire('Erro!', response.message, 'error');
                        }
                        $('#mensagem').text('');
                    },
                    error: function(){
                        Swal.fire('Erro!', 'Erro de comunicação ao tentar importar.', 'error');
                        $('#mensagem').text('');
                    }
                });
            }
        });
    });
});
</script>
