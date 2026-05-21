$(document).ready(function() {
    var table = $('#data_table').DataTable({
        ajax: './api/listar-clientes.php?listar_categorias',
        processing: true,
        serverSide: true,
        language: {
            url: './js/datatables/pt_br.json'
        },
        layout: {
            topStart: null,
            bottom: 'paging',
            bottomStart: "info",
            bottomEnd: null
        },
        columns: [
            {
                data: "category_id",
                className: "text-center"
            }, // ID
            {
                data: "category_name",
                className: "text-center"
            }, // URL
            {
                orderable: false,
                data: "type",
                className: "text-center"
            }, // Tipo
            {
                //orderable: false, // Pode ser ordenado
                data: "is_adult",
                className: "text-center"
            },
            {
                orderable: false,
                data: "bg",
                className: "text-center"
            },
            {
                orderable: false,
                data: "acao",
                className: "text-center acao"
            }, // Ações
        ],
        // AQUI ESTÁ A CORREÇÃO: ordenando pela coluna `position` (índice 5 no array de colunas)
        order: [[5, 'asc']]
    });
});