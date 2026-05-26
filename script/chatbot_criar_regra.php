<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// A LÓGICA PHP VEM PRIMEIRO, ANTES DE QUALQUER HTML
require_once("chatbot_integrado_funcoes.php");

if (!isset($_SESSION['admin_id'])) { 
    // Se não estiver logado, redireciona antes de carregar o menu
    header("Location: ./index.php");
    exit; 
}
$admin_id = $_SESSION['admin_id'];
$feedback = '';

// Processa o formulário ANTES de carregar o menu.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['rule_type'], $_POST['rule_action'], $_POST['response'], $_POST['messages']) && !empty($_POST['messages'])) {
        addChatBotRule($admin_id, $_POST['rule_type'], $_POST['rule_action'], $_POST['response'], $_POST['messages']);
        // Agora o redirecionamento vai funcionar!
        header("Location: chatbot_regras.php?feedback=success_add");
        exit;
    } else {
        $feedback = "<div class='alert alert-warning'>Tipo, Ação e pelo menos uma Mensagem (Gatilho) são obrigatórios.</div>";
    }
}

// AGORA SIM, CARREGAMOS A PARTE VISUAL
require_once("menu.php");
?>

<style>
    /* Seu CSS de design aqui */
    .badge { display: inline-flex; align-items: center; }
    .remove-btn { margin-left: 8px; color: white; text-decoration: none; font-weight: bold; cursor: pointer; }
    .remove-btn:hover { color: #ddd; }
</style>

<div class="page-content" style="padding: 20px;">
    <h3><i class="fas fa-plus-circle"></i> Criar Nova Regra</h3>
    
    <?php echo $feedback; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Informações da Regra</h5>
        </div>
        <form method="POST" action="chatbot_criar_regra.php">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Tipo da Regra</label>
                        <select name="rule_type" class="form-select">
                            <option value="equals">Igual a (precisa ser exato)</option>
                            <option value="contains">Contém (basta ter a palavra)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Ação da Regra</label>
                        <select name="rule_action" class="form-select">
                            <option value="text">Enviar Texto Fixo</option>
                            <option value="test_iptv">Gerar e Enviar Teste IPTV</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Mensagens / Gatilhos</label>
                        <div class="input-group">
                            <input type="text" id="message_input" class="form-control" placeholder="Digite uma mensagem e clique em Adicionar">
                            <button type="button" class="btn btn-info" id="add_message_btn">Adicionar</button>
                        </div>
                        <small class="text-muted">As mensagens que irão disparar esta regra.</small>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Gatilhos Adicionados:</label>
                        <div id="message_list" class="mt-2"></div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Resposta</label>
                        <textarea class="form-control" rows="6" name="response" placeholder="Se a ação for 'Enviar Texto', digite a resposta aqui..."></textarea>
                        <small class="text-muted">Se a ação for 'Gerar Teste', este campo é ignorado.</small>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Salvar Regra</button>
                <a href="chatbot_regras.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('add_message_btn');
    const messageInput = document.getElementById('message_input');
    const messageList = document.getElementById('message_list');
    
    function adicionarGatilho() {
        const messageText = messageInput.value.trim();
        if (messageText === '') return;
        
        const badge = document.createElement('span');
        badge.className = 'badge bg-secondary m-1 p-2';
        
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'messages[]';
        hiddenInput.value = messageText;
        
        const textNode = document.createTextNode(messageText);
        
        const removeBtn = document.createElement('a');
        removeBtn.href = '#';
        removeBtn.innerHTML = '&times;';
        removeBtn.className = 'remove-btn';
        removeBtn.onclick = function(e) {
            e.preventDefault();
            badge.remove();
        };
        
        badge.appendChild(textNode);
        badge.appendChild(hiddenInput);
        badge.appendChild(removeBtn);
        
        messageList.appendChild(badge);
        
        messageInput.value = '';
        messageInput.focus();
    }

    addBtn.addEventListener('click', adicionarGatilho);
    
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); 
            adicionarGatilho();
        }
    });
});
</script>