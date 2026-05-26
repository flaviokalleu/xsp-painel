<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0) {
    header("Location: ./clientes.php");
    exit();
}
require_once("menu.php");
?>

<!-- CSS do DataTables e do RowReorder -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/rowreorder/1.5.0/css/rowReorder.dataTables.min.css">

<style>
    .nav-tabs .nav-link.active {
        background-color: #304b73;
        color: white;
        border-color: #304b73;
    }
</style>

<h4 class="align-items-center d-flex justify-content-between mb-4 text-muted text-uppercase">
    ORGANIZAR Categorias
    <div>
        <button type="button" id="saveOrderBtn" class="btn btn-info me-2" style="display: none;">
            <i class="fas fa-save me-1"></i> Salvar Ordem
        </button>
        <button type="button" class="btn btn-outline-success fa-plus fas" onclick='modal_master("api/categorias.php", "add_categoria", "add")'></button>
    </div>
</h4>

<ul class="nav nav-tabs mb-3" id="categoryTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" data-type="streams" type="button">Canais</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-type="movie" type="button">Filmes</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-type="series" type="button">Séries</button>
  </li>
</ul>

<div class="alert alert-info">
    <i class="fas fa-arrows-alt me-2"></i>
    <strong>Instrução:</strong> Clique e arraste a coluna <strong>#</strong> para reordenar as categorias do tipo selecionado.
</div>

<table id="data_table" class="display overflow-auto table" style="width: 100%;">
    <thead class="table-dark">
        <tr>
            <th style="min-width: 75px; cursor: grab;">#</th>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Adulto</th>
            <th>BG SSIPTV</th>
            <th style="min-width: 191px;">Ações</th>
        </tr>
    </thead>
</table>

<!-- Modal master -->
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

</div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="//cdn.datatables.net/2.0.7/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/rowreorder/1.5.0/js/dataTables.rowReorder.min.js"></script>
<script src="./js/sweetalert2.js"></script>
<script src="./js/custom.js"></script>

<script>
$(document).ready(function() {
    let currentCategoryType = 'streams';

    var table = $('#data_table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "api/categorias.php",
            "type": "POST",
            "data": function(d) {
                d.action = "listar";
                d.type = currentCategoryType;
            }
        },
        "columns": [
            { "data": "id_categoria", "className": "reorder text-center" },
            { "data": "category_name" },
            { "data": "tipo", "className": "text-center" },
            { "data": "is_adult", "className": "text-center" },
            { "data": "bg_ssiptv", "className": "text-center" },
            { "data": "acoes", "className": "text-center", "orderable": false }
        ],
        "order": [],
        "language": { "url": "./js/datatables/pt_br.json" },
        rowReorder: {
            selector: 'td.reorder',
            dataSrc: 'id_categoria',
            update: false
        }
    });

    $('#categoryTabs button').on('click', function (e) {
        e.preventDefault();
        $('#categoryTabs button').removeClass('active');
        $(this).addClass('active');
        currentCategoryType = $(this).data('type');
        table.ajax.reload();
        $('#saveOrderBtn').fadeOut();
    });

    table.on('row-reorder', function(e, diff, edit) {
        $('#saveOrderBtn').prop('disabled', false).fadeIn();
    });

    $('#saveOrderBtn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Salvando...');

        var data = table.rows().data().toArray();
        var orderData = data.map(function(row, index) {
            return { id: row.id_categoria, ordem: index + 1 };
        });

        // **CORREÇÃO DEFINITIVA**: Volta a usar o método de envio de formulário padrão, que é mais compatível.
        $.ajax({
            url: 'api/categorias.php',
            type: 'POST',
            data: {
                action: 'save_order',
                order: JSON.stringify(orderData) // Envia a ordem como uma string de texto.
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Sucesso!', response.message, 'success');
                    btn.fadeOut();
                    table.ajax.reload(null, false); 
                } else {
                    Swal.fire('Erro!', response.message || 'Não foi possível salvar a ordem.', 'error');
                }
            },
            error: function() {
                Swal.fire('Erro de Conexão', 'Não foi possível se comunicar com o servidor.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Salvar Ordem');
            }
        });
    });
});
</script>

</body>
</html>
