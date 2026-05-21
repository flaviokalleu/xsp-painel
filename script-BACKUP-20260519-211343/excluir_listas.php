<?php
// Inclui o menu e o cabeçalho do seu painel para manter o layout
require_once 'menu.php';
?>

<!-- Adiciona o CSS e JS para o seletor de datas (Date Range Picker) -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<!-- Conteúdo da Página -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Excluir Listas Expiradas / Testes</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-md-10 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Configurações de Exclusão</h5>
                    
                    <!-- Formulário para enviar os dados -->
                    <form id="form_excluir_listas">
                        <div class="mb-4">
                            <label for="daterange" class="form-label">Intervalo de data de vencimento:</label>
                            <input type="text" id="daterange" name="daterange" class="form-control" />
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Tipo de lista a excluir:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tipos[]" value="testes" id="check_testes">
                                <label class="form-check-label" for="check_testes">
                                    Testes (usuários marcados como "is_trial" = 1)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tipos[]" value="expiradas" id="check_expiradas">
                                <label class="form-check-label" for="check_expiradas">
                                    Expiradas (usuários com data de vencimento no passado)
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger w-100">Excluir Listas</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    // Inicializa o seletor de datas
    $('#daterange').daterangepicker({
        opens: 'center',
        locale: {
          format: 'DD/MM/YYYY',
          separator: ' - ',
          applyLabel: 'Aplicar',
          cancelLabel: 'Cancelar',
          fromLabel: 'De',
          toLabel: 'Até',
          customRangeLabel: 'Personalizado',
          weekLabel: 'S',
          daysOfWeek: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
          monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
          firstDay: 1
        }
    });

    // Manipula o envio do formulário
    $('#form_excluir_listas').on('submit', function(e) {
        e.preventDefault(); // Impede o envio padrão do formulário

        var formData = $(this).serialize();
        var tiposSelecionados = $('input[name="tipos[]"]:checked').length > 0;

        if (!tiposSelecionados) {
            Swal.fire('Atenção!', 'Você precisa selecionar pelo menos um tipo de lista para excluir (Testes ou Expiradas).', 'warning');
            return;
        }

        Swal.fire({
            title: 'Você tem CERTEZA?',
            html: "Esta ação irá excluir permanentemente os usuários que correspondem aos critérios selecionados.<br><br><strong style='color: red;'>ESTA AÇÃO É IRREVERSÍVEL!</strong>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, quero excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Adiciona o token e a ação aos dados do formulário
                var dataToSend = formData + '&action=excluir_listas&token=' + (typeof SESSION_TOKEN !== 'undefined' ? SESSION_TOKEN : '');

                $.ajax({
                    type: 'POST',
                    url: 'api/acoes_excluir.php',
                    data: dataToSend,
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Excluindo...',
                            html: 'Por favor, aguarde enquanto as listas são removidas.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('Sucesso!', response.message, 'success');
                        } else {
                            Swal.fire('Erro!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Erro!', 'Falha na comunicação com o servidor.', 'error');
                    }
                });
            }
        });
    });
});
</script>
