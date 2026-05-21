<?php
require_once("menu.php");
?>

<h4 class="align-items-center d-flex mb-4 text-primary text-uppercase">
    <i class="fa-solid fa-link me-2 text-primary"></i> SISTEMA DE ALTERAÇÃO EM MASSA
</h4>
<div class="card p-4 shadow-sm border-primary">
    <div class="card-header bg-primary text-white mb-4" style="border-radius: 0.25rem 0.25rem 0 0; margin: -1rem -1rem 1.5rem -1rem;">
        <h5 class="my-0">Configurações de Links</h5>
    </div>
    
    <p class="text-secondary">Utilize o formulário abaixo para atualizar os links do servidor.</p>
    <hr class="mb-4">

    <form id="form_update_links">
        <div class="mb-4">
            <label for="link_m3u" class="form-label fw-bold">Link da M3U</label>
            <input type="text" class="form-control border-primary" id="link_m3u" name="link_m3u"
                   placeholder="Ex: http://antigo.server.com/playlist.m3u" required>
        </div>
        <div class="mb-4">
            <label for="nova_url" class="form-label fw-bold">Nova URL</label>
            <input type="text" class="form-control border-primary" id="nova_url" name="nova_url"
                   placeholder="Ex: http://novo.server.com">
        </div>
        <button type="submit" class="btn btn-primary btn-lg mt-3">
            <i class="fa-solid fa-arrows-rotate me-2"></i> Atualizar em massa
        </button>
    </form>
</div>

<script src="//cdn.datatables.net/2.0.7/js/dataTables.js"></script>
<script src="./js/sweetalert2.js"></script>
<script src="./js/datatablecanais.js?sfd"></script>
<script src="./js/custom.js"></script>

<script>
    $(document).ready(function() {
        $('#form_update_links').on('submit', function(e) {
            e.preventDefault();

            const link_m3u = $('#link_m3u').val();
            const nova_url = $('#nova_url').val();

            Swal.fire({
                title: 'Atualizando...',
                text: 'Isso pode levar alguns segundos. Por favor, aguarde.',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            $.ajax({
                url: './api/alterar_links.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    link_m3u: link_m3u,
                    nova_url: nova_url
                }),
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Sucesso!',
                            text: response.message,
                            icon: 'success'
                        });
                    } else {
                        Swal.fire({
                            title: 'Erro!',
                            text: response.message,
                            icon: 'error'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Erro de comunicação!',
                        text: 'Não foi possível se comunicar com o servidor.',
                        icon: 'error'
                    });
                }
            });
        });
    });
</script>

</div>
</main>
<div class="modal fade" id="modal_master" tabindex="-1" aria-labelledby="modal_master" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="d-block modal-header" id="modal_master-header">
                <h5 class="float-start modal-title" id="modal_master-titulo"></h5>
                <button type="button" class="fa btn text-white fa-close fs-6 float-end" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="modal_master_form" onsubmit="event.preventDefault();" autocomplete="off">
                <div id="modal_master-body" class="modal-body overflow-auto" style="max-height: 421px;"></div>
                <div id="modal_master-footer" class="modal-footer"></div>
            </form>
        </div>
    </div>
</div>
</body>
</html>