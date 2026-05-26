<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../api/controles/db.php';

$conexao = conectar_bd();
$clientes_p2p = [];

if ($conexao) {
    try {
        $stmt = $conexao->prepare("SELECT id, usuario, name, whatsapp, Vencimento FROM clientes WHERE is_p2p = 1 ORDER BY id DESC");
        $stmt->execute();
        $clientes_p2p = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Em um app real, você logaria este erro
        die("Erro ao buscar clientes: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Clientes P2P</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> </head>
<body>
    <div class="container">
        <h1><i class="fas fa-network-wired"></i> Gerenciador de Clientes P2P</h1>

        <?php if (isset($_SESSION['mensagem_renovacao'])): ?>
            <div class="renewal-message-box">
                <button class="close-btn" onclick="closeRenewalBox()" title="Fechar">&times;</button>
                <h4>🎉 Cliente Renovado! 🎉</h4>
                <pre class="message-content" id="renewal-message"><?php echo htmlspecialchars($_SESSION['mensagem_renovacao']); ?></pre>
                <div class="message-actions">
                    <button class="btn btn-primary" onclick="copyToClipboard()"><i class="fas fa-copy"></i> Copiar Mensagem</button>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($_SESSION['mensagem_renovacao']); ?>" target="_blank" class="btn btn-success"><i class="fab fa-whatsapp"></i> Enviar no WhatsApp</a>
                </div>
            </div>
            <?php unset($_SESSION['mensagem_renovacao']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert <?php echo $_SESSION['msg_type']; ?>">
                <?php 
                    echo $_SESSION['mensagem']; 
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['msg_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="actions">
            <a href="criar_usuario.php" class="btn btn-primary">Criar Novo Usuário P2P</a>
            <form action="action_gerar_teste.php" method="POST" style="margin: 0;">
                <button type="submit" class="btn btn-success">Gerar Teste 4 Horas</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Código (Usuário)</th>
                    <th>Nome / Descrição</th>
                    <th>WhatsApp</th>
                    <th>Vencimento</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($clientes_p2p)): ?>
                    <?php foreach ($clientes_p2p as $cliente): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cliente['usuario']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['name']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['whatsapp']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($cliente['Vencimento'])); ?></td>
                            <td>
                                <a href="renovar_form.php?id=<?php echo $cliente['id']; ?>" class="btn btn-warning">Renovar</a>
                                <a href="action_excluir_usuario.php?id=<?php echo $cliente['id']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Nenhum cliente P2P encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    function copyToClipboard() {
        const messageElement = document.getElementById('renewal-message');
        const textToCopy = messageElement.innerText;
        navigator.clipboard.writeText(textToCopy).then(() => {
            const copyButton = document.querySelector('.renewal-message-box .btn-primary');
            copyButton.innerHTML = '<i class="fas fa-check"></i> Copiado!';
            setTimeout(() => {
                copyButton.innerHTML = '<i class="fas fa-copy"></i> Copiar Mensagem';
            }, 2000); // Volta ao texto original após 2 segundos
        }).catch(err => {
            console.error('Erro ao copiar texto: ', err);
        });
    }

    function closeRenewalBox() {
        const box = document.querySelector('.renewal-message-box');
        if (box) {
            box.style.display = 'none';
        }
    }
    </script>

</body>
</html>