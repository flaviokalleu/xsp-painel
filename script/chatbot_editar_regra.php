<?php
session_start();
require_once("chatbot_integrado_funcoes.php");

if (!isset($_SESSION['admin_id'])) { 
    header("Location: ./index.php");
    exit; 
}
$admin_id = $_SESSION['admin_id'];
$feedback = '';

$rule_id = intval($_GET['id'] ?? 0);
$rule = getChatbotRuleById($rule_id, $admin_id);

if (!$rule) {
    // Redireciona se a regra não for encontrada ou não pertencer ao admin
    header("Location: chatbot_regras.php?feedback=error_not_found");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['rule_type'], $_POST['rule_action'], $_POST['response'], $_POST['messages']) && !empty($_POST['messages'])) {
        updateChatBotRule($admin_id, $rule_id, $_POST['rule_type'], $_POST['rule_action'], $_POST['response'], $_POST['messages']);
        header("Location: chatbot_regras.php?feedback=success_edit");
        exit;
    } else {
        $feedback = "<div class='alert alert-warning'>Tipo, Ação e pelo menos uma Mensagem (Gatilho) são obrigatórios.</div>";
    }
}

require_once("menu.php");
?>

<!-- CSS -->
<style>
    .badge { display: inline-flex; align-items: center; }
    .remove-btn { margin-left: 8px; color: white; text-decoration: none; font-weight: bold; cursor: pointer; }
</style>

<div class="page-content" style="padding: 20px;">
    <h3><i class="fas fa-edit"></i> Editar Regra #<?php echo $rule['id']; ?></h3>
    
    <?php echo $feedback; ?>

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Informações da Regra</h5></div>
        <form method="POST" action="chatbot_editar_regra.php?id=<?php echo $rule_id; ?>">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Tipo da Regra</label>
                        <select name="rule_type" class="form-select">
                            <option value="equals" <?php if($rule['rule_type'] == 'equals') echo 'selected'; ?>>Igual a</option>
                            <option value="contains" <?php if($rule['rule_type'] == 'contains') echo 'selected'; ?>>Contém</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Ação da Regra</label>
                        <select name="rule_action" class="form-select">
                            <option value="text" <?php if($rule['rule_action'] == 'text') echo 'selected'; ?>>Enviar Texto Fixo</option>
                            <option value="test_iptv" <?php if($rule['rule_action'] == 'test_iptv') echo 'selected'; ?>>Gerar e Enviar Teste IPTV</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Mensagens / Gatilhos</label>
                        <div class="input-group">
                            <input type="text" id="message_input" class="form-control" placeholder="Digite uma mensagem e clique em Adicionar">
                            <button type="button" class="btn btn-info" id="add_message_btn">Adicionar</button>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Gatilhos Adicionados:</label>
                        <div id="message_list" class="mt-2">
                            <?php foreach($rule['messages'] as $message): ?>
                                <span class="badge bg-secondary m-1 p-2">
                                    <?php echo htmlspecialchars($message); ?>
                                    <input type="hidden" name="messages[]" value="<?php echo htmlspecialchars($message); ?>">
                                    <a href="#" class="remove-btn">&times;</a>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Resposta</label>
                        <textarea class="form-control" rows="6" name="response"><?php echo htmlspecialchars($rule['response']); ?></textarea>
                         <small class="text-muted">Se a ação for 'Gerar Teste', este campo é ignorado.</small>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Salvar Alterações</button>
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
    
    function adicionarGatilho(messageText) {
        if (messageText.trim() === '') return;
        
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
        removeBtn.onclick = function(e) { e.preventDefault(); badge.remove(); };
        
        badge.appendChild(textNode);
        badge.appendChild(hiddenInput);
        badge.appendChild(removeBtn);
        messageList.appendChild(badge);
    }

    addBtn.addEventListener('click', function() {
        adicionarGatilho(messageInput.value);
        messageInput.value = '';
        messageInput.focus();
    });

    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            adicionarGatilho(messageInput.value);
            messageInput.value = '';
        }
    });

    // Adiciona o evento de clique para os botões de remover que já existem
    messageList.querySelectorAll('.remove-btn').forEach(btn => {
        btn.onclick = function(e) {
            e.preventDefault();
            btn.parentElement.remove();
        };
    });
});
</script>
