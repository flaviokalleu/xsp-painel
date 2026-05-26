<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Verifica se a sessão está iniciada e se a variável de sessão existe e tem o valor desejado
if (isset($_SESSION['plano_admin']) && $_SESSION['plano_admin'] == 1) {
    // Redireciona para clientes.php
    header("Location: ./clientes.php");
    exit(); // Termina o script após o redirecionamento
}

require_once("menu.php");
?>

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3 p-4">
        <h4 class="card-title text-primary d-flex justify-content-between align-items-center mb-4">
            <span class="fw-bold">LISTAR REVENDEDEDORES</span>
            <button type="button" class="btn btn-primary rounded-pill" onclick='modal_master("api/revendedores.php", "add_revendedor", "add")'>
                <i class="fas fa-plus me-2"></i> Adicionar
            </button>
        </h4>
        <div class="table-responsive">
            <table id="data_table" class="display table table-striped table-hover border" style="width: 100%;">
                <thead class="bg-primary text-white">
                    <tr>
                        <th style="min-width: 75px;">#</th>
                        <th>Usuário</th>
                        <th>Créditos</th>
                        <th>Tipo</th>
                        <th>Total Revendedores</th>
                        <th>Total Clientes</th>
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
<script src="./js/revendedores.js?sfd"></script>
<script src="./js/custom.js"></script>

</div> </main> <div class="modal fade" id="modal_master" tabindex="-1" aria-labelledby="modal_master" aria-hidden="true">
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

<div class="modal fade" id="infoRevendedorModal" tabindex="-1" aria-labelledby="infoRevendedorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-success text-white rounded-top-4">
                <h5 class="modal-title" id="infoRevendedorModalLabel">Revendedor Criado com Sucesso!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p>Copie a mensagem de boas-vindas abaixo:</p>
                <div id="dadosRevendedorParaCopiar" class="bg-light p-3 rounded border" style="white-space: pre-wrap; word-wrap: break-word;">
                    </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary rounded-pill" onclick="copyText('dadosRevendedorParaCopiar')">
                    <i class="fas fa-copy me-2"></i>Copiar Mensagem
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="infoCreditosModal" tabindex="-1" aria-labelledby="infoCreditosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-info text-white rounded-top-4">
                <h5 class="modal-title" id="infoCreditosModalLabel">Operação de Créditos Concluída!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p>Copie a mensagem de confirmação abaixo:</p>
                <div id="dadosCreditosParaCopiar" class="bg-light p-3 rounded border" style="white-space: pre-wrap; word-wrap: break-word;">
                    </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary rounded-pill" onclick="copyText('dadosCreditosParaCopiar')">
                    <i class="fas fa-copy me-2"></i>Copiar Mensagem
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Função para copiar o texto formatado para o WhatsApp
    function copyText(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const textToCopy = element.getAttribute('data-copy-text');

        if (textToCopy) {
            xspCopiar(textToCopy, () => {
                SweetAlert3('Copiado para a área de transferência!', 'success');
            });
        }
    }
</script>

</body>
</html>