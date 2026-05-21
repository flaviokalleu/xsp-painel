<?php
require_once("menu.php");
?>
<h4 class="align-items-center d-flex mb-4 text-primary text-uppercase">
    <i class="fa-solid fa-gear me-2 text-primary"></i> SISTEMA DE PERSONALIZAÇÃO
</h4>
<div class="card p-4 shadow-sm border-primary">
    <p>Utilize os campos abaixo para personalizar seu painel.</p>
    <hr class="mb-4">

    <div class="card p-4 shadow-sm mb-4">
        <h5>Título do site</h5>
        <p class="text-secondary">Esse campo vai alterar o nome do seu site.</p>
        <form id="form_titulo" method="POST">
            <div class="input-group mb-3">
                <input type="text" class="form-control" name="titulo" placeholder="Digite o novo título" required>
                <button class="btn btn-primary" type="submit">Atualizar Título</button>
            </div>
            <input type="hidden" name="action" value="update_title">
        </form>
    </div>

    <div class="card p-4 shadow-sm">
        <h5>Logo da empresa</h5>
        <p class="text-secondary">O tamanho recomendado é 170x50 ou 250x50</p>
        <form id="form_logo" enctype="multipart/form-data" method="POST">
            <div class="input-group">
                <input type="file" class="form-control" name="logo" required>
                <button class="btn btn-primary" type="submit">Enviar Logo</button>
            </div>
            <input type="hidden" name="action" value="update_logo">
        </form>
    </div>
</div>

<script>
    // Script JavaScript para enviar os formulários via AJAX
    $(document).ready(function() {
        $('#form_titulo').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const formData = new FormData(form[0]);

            Swal.fire({
                title: 'Atualizando...',
                text: 'Por favor, aguarde.',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            $.ajax({
                url: './api/personalizar.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.fire({
                        title: response.status === 'success' ? 'Sucesso!' : 'Erro!',
                        text: response.message,
                        icon: response.status,
                    }).then(() => {
                        if (response.status === 'success') {
                            location.reload();
                        }
                    });
                },
                error: function() {
                    Swal.fire('Erro de Comunicação!', 'Não foi possível se comunicar com o servidor.', 'error');
                }
            });
        });

        $('#form_logo').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const formData = new FormData(form[0]);

            Swal.fire({
                title: 'Enviando Logo...',
                text: 'Por favor, aguarde.',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            $.ajax({
                url: './api/personalizar.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.fire({
                        title: response.status === 'success' ? 'Sucesso!' : 'Erro!',
                        text: response.message,
                        icon: response.status,
                    }).then(() => {
                        if (response.status === 'success') {
                            location.reload();
                        }
                    });
                },
                error: function() {
                    Swal.fire('Erro de Comunicação!', 'Não foi possível se comunicar com o servidor.', 'error');
                }
            });
        });
    });
</script>

<div class="modal fade" id="modal_master" tabindex="-1" aria-labelledby="modal_master" aria-hidden="true">
    </div>
</body>
</html>