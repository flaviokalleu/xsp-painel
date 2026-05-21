<?php
session_start();
require_once("menu.php");
?>
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3 p-4">
        <h4 class="card-title text-primary d-flex justify-content-between align-items-center mb-4">
            <span class="fw-bold">Clientes</span>
            <button type="button" class="btn btn-primary rounded-pill" onclick='modal_master("api/clientes.php", "adicionar_clientes", "add")'>
                <i class="fas fa-plus me-2"></i> Adicionar
            </button>
        </h4>
        <div class="table-responsive">
            <table id="data_table" class="display table table-striped table-hover border" style="width: 100%;">
                <thead class="bg-primary text-white">
                    <tr>
                        <th style="min-width: 75px;">#</th>
                        <th>Nome</th>
                        <th>Usuário</th>
                        <th>Indicados</th>
                        <th>Status</th>
                        <th>Vencimento</th>
                        <th style="min-width: 191px;">Ações</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script src="//cdn.datatables.net/2.0.7/js/dataTables.js"></script>
<script src="./js/sweetalert2.js"></script>
<script src="./js/datatableclientes.js?sfd"></script>
<script src="./js/custom.js?v=1"></script>

</div>
</main>

<div class="modal fade" id="modal_master" tabindex="-1" aria-labelledby="modal_master" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title" id="modal_master-titulo"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="modal_master_form" onsubmit="event.preventDefault();" autocomplete="off">
                <div id="modal_master-body" class="modal-body p-4 overflow-auto" style="max-height: 421px;"></div>
                <div id="modal_master-footer" class="modal-footer border-top-0"></div>
            </form>
        </div>
    </div>
</div>
<script>
    function copyText(elementId) {
        var preElement = document.getElementById(elementId);
        var range = document.createRange();
        range.selectNodeContents(preElement);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        document.execCommand('copy');
        selection.removeAllRanges();
        SweetAlert3('Texto copiado para a área de transferência!', 'success');
    }
</script>

</body>
</html>