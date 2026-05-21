<?php
/*
================================================================================
|             PÁGINA DE GERENCIAMENTO (gerenciar_revendedores.php)             |
|             Corrigido: Nome da coluna de 'credits' para 'creditos'.          |
================================================================================
*/

session_start();
// Descomente e ajuste a verificação de sessão do seu painel
// if (empty($_SESSION['logged_in_fxtream'])) {
//     header('Location: ./index.php');
//     exit();
// }

// Inclui o arquivo de conexão do seu painel
// Verifique se o caminho está correto
require_once(__DIR__ . '/api/controles/db.php');

// Simula um usuário logado para testes. Substitua pela lógica real do seu painel.
$logged_user = ['id' => 1, 'username' => 'admin']; // Exemplo: usuário admin logado

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Revendedores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
    <style>
        .table-hover tbody tr:hover {
            background-color: rgba(var(--bs-emphasis-color-rgb), 0.05);
        }
    </style>
</head>
<body class="p-3 p-md-4">
     <div class="d-grid mb-4">
            <a href="/dashboard.php" class="btn btn-success btn-lg" style="background-color: #28a745; border-color: #28a745;">
                <i class="fas fa-home"></i> Voltar ao Início
            </a>
        </div>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white text-center">
                <h2 class="mb-0"><i class="fas fa-users-cog"></i> Gestão de Créditos e Revendedores</h2>
            </div>
            <div class="card-body p-4">
                <h4 class="mb-3">Seus Revendedores</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Revendedor</th>
                                <th>Créditos</th>
                                <th>Criado por</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="resellers-table-body">
                            <tr>
                                <td colspan="5" class="text-center">Carregando dados...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Adicionar/Remover Créditos -->
    <div class="modal fade" id="creditsModal" tabindex="-1" aria-labelledby="creditsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="creditsModalLabel">Gerenciar Créditos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="creditsForm">
                        <input type="hidden" id="resellerIdInput" name="reseller_id">
                        <p>Revendedor: <strong id="resellerName"></strong></p>
                        <div class="mb-3">
                            <label for="creditsAmount" class="form-label">Quantidade de Créditos</label>
                            <input type="number" class="form-control" id="creditsAmount" name="credits" placeholder="Use valores negativos para remover" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Motivo (Opcional)</label>
                            <input type="text" class="form-control" id="reason" name="reason" placeholder="Ex: Pagamento mensal">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveCreditsButton">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            function loadResellers() {
                $.ajax({
                    url: 'api_revendedores.php?action=get_resellers',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        let tableBody = $('#resellers-table-body');
                        tableBody.empty();
                        if (response.success && response.data.length > 0) {
                            response.data.forEach(reseller => {
                                // CORREÇÃO: Usando 'creditos' e 'user'
                                let row = `
                                    <tr>
                                        <td>${reseller.id}</td>
                                        <td>${reseller.user}</td> 
                                        <td><strong>${reseller.creditos}</strong></td>
                                        <td>${reseller.owner_name || '-'}</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary manage-credits-btn" data-id="${reseller.id}" data-name="${reseller.user}" title="Gerenciar Créditos">
                                                <i class="fas fa-dollar-sign"></i> Créditos
                                            </button>
                                            <a href="log_creditos.php?reseller_id=${reseller.id}" class="btn btn-sm btn-outline-secondary" title="Ver Histórico">
                                                <i class="fas fa-history"></i> Histórico
                                            </a>
                                        </td>
                                    </tr>
                                `;
                                tableBody.append(row);
                            });
                        } else {
                            tableBody.html('<tr><td colspan="5" class="text-center">Nenhum revendedor encontrado.</td></tr>');
                        }
                    },
                    error: function() {
                         $('#resellers-table-body').html('<tr><td colspan="5" class="text-center">Erro ao carregar os dados.</td></tr>');
                    }
                });
            }

            $(document).on('click', '.manage-credits-btn', function() {
                let resellerId = $(this).data('id');
                let resellerName = $(this).data('name');
                $('#resellerIdInput').val(resellerId);
                $('#resellerName').text(resellerName);
                $('#creditsForm')[0].reset();
                var creditsModal = new bootstrap.Modal(document.getElementById('creditsModal'));
                creditsModal.show();
            });

            $('#saveCreditsButton').on('click', function() {
                let resellerId = $('#resellerIdInput').val();
                let credits = $('#creditsAmount').val();
                let reason = $('#reason').val();

                if (!credits || credits == 0) {
                    Swal.fire('Atenção', 'Por favor, insira uma quantidade de créditos válida.', 'warning');
                    return;
                }

                $.ajax({
                    url: 'api_revendedores.php?action=change_credits',
                    method: 'POST',
                    data: {
                        reseller_id: resellerId,
                        credits: credits,
                        reason: reason
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Sucesso!', 'Créditos atualizados com sucesso.', 'success');
                            bootstrap.Modal.getInstance(document.getElementById('creditsModal')).hide();
                            loadResellers();
                        } else {
                            Swal.fire('Erro!', response.message || 'Não foi possível atualizar os créditos.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Erro!', 'Ocorreu um problema de comunicação com o servidor.', 'error');
                    }
                });
            });
            loadResellers();
        });
    </script>
</body>
</html>
