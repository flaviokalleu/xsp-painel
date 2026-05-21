/**
 * SCRIPT COMPLETO E ATUALIZADO PARA GERENCIAR CATEGORIAS
 * Funcionalidades: Listar, Reordenar com Drag-and-Drop, e Editar (ciclo completo).
 * Versão: 2.0
 */
$(document).ready(function() {

    // --- 1. INICIALIZAÇÃO DO DATATABLES ---
    // A tabela é inicializada com as configurações para reordenação.
    var table = $('#data_table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "api/categorias.php",
            "type": "POST",
            "data": function(d) {
                // Adiciona a ação 'listar' e o tipo de categoria selecionado (Canais, Filmes, Séries)
                d.action = "listar";
                d.type = $('#category-tabs .nav-link.active').data('type') || 'streams';
            }
        },
        "columns": [
            { "data": "position", "className": "reorder text-center" }, // Usando a coluna 'position' real para ordenação
            { "data": "category_name" },
            { "data": "tipo", "className": "text-center" },
            { "data": "is_adult", "className": "text-center" },
            { "data": "bg_ssiptv", "className": "text-center" },
            { "data": "acoes", "className": "text-center", "orderable": false }
        ],
        "order": [[0, 'asc']], // Ordenação inicial pela coluna 'position'
        "language": {
            "url": "./js/datatables/pt_br.json" // Verifique se o caminho está correto
        },
        rowReorder: {
            selector: 'td.reorder',
            dataSrc: 'position', // Usar a coluna 'position' como fonte
            update: false // Faremos a atualização manualmente no botão
        }
    });

    // --- 2. LÓGICA PARA RECARREGAR A TABELA AO MUDAR DE ABA (Canais, Filmes, Séries) ---
    $('#category-tabs .nav-link').on('click', function() {
        table.ajax.reload();
    });

    // --- 3. LÓGICA DA REORDENAÇÃO (DRAG-AND-DROP) ---
    table.on('row-reorder', function(e, diff, edit) {
        // Mostra o botão "Salvar Ordem" quando o usuário reordena
        $('#saveOrderBtn').prop('disabled', false).fadeIn();
    });

    $('#saveOrderBtn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Salvando...');

        var data = table.rows().data().toArray();
        var orderData = data.map(function(row, index) {
            return {
                id: row.id_categoria, // 'id_categoria' é o alias que definimos no PHP
                ordem: index + 1
            };
        });

        $.ajax({
            url: 'api/categorias.php',
            type: 'POST',
            data: {
                action: 'save_order',
                order: JSON.stringify(orderData)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Sucesso!', response.message, 'success');
                    btn.fadeOut();
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire('Erro!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Erro de Conexão!', 'Não foi possível salvar a ordem.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Salvar Ordem');
            }
        });
    });

    // --- 4. LÓGICA DA EDIÇÃO (NOVO E MELHORADO) ---

    // Listener de clique para os botões de EDITAR na tabela
    // Este método é o correto para DataTables, pois funciona mesmo após a paginação ou recarregamento.
    $('#data_table tbody').on('click', '.edit-btn', function() {
        var id = $(this).data('id'); // Pega o ID do atributo data-id que criamos no PHP

        $.ajax({
            url: 'api/categorias.php',
            type: 'POST',
            data: { action: 'get_category', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Preenche os campos do seu modal de edição
                    // IMPORTANTE: Verifique se os IDs abaixo correspondem ao seu HTML
                    $('#edit_id').val(response.data.id);
                    $('#edit_nome').val(response.data.nome);
                    $('#edit_is_adult').val(response.data.is_adult);

                    // Abre o modal de edição
                    $('#modal_edit_categoria').modal('show'); // Use o ID do seu modal
                } else {
                    Swal.fire('Erro!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Erro de Conexão!', 'Não foi possível buscar os dados da categoria.', 'error');
            }
        });
    });

    // Listener de clique para o botão SALVAR DENTRO do modal de edição
    $('#btn_salvar_edicao').on('click', function() { // Use o ID do seu botão de salvar
        const btn = $(this);
        btn.prop('disabled', true).html('Salvando...');

        $.ajax({
            url: 'api/categorias.php',
            type: 'POST',
            data: {
                action: 'save_edit',
                id: $('#edit_id').val(),
                nome: $('#edit_nome').val(),
                type: $('#category-tabs .nav-link.active').data('type') || 'streams', // Pega o tipo da aba ativa
                is_adult: $('#edit_is_adult').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#modal_edit_categoria').modal('hide');
                    Swal.fire('Sucesso!', response.message, 'success');
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire('Erro!', response.message || 'Ocorreu um erro desconhecido.', 'error');
                }
            },
            error: function() {
                Swal.fire('Erro de Conexão!', 'Não foi possível salvar as alterações.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('Salvar Alterações');
            }
        });
    });

});