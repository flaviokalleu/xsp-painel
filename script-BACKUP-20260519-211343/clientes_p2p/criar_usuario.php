<?php
require_once '../api/controles/db.php';
$conexao = conectar_bd();
$planos = [];
if ($conexao) {
    // MODIFICADO: Removida a coluna 'duracao_dias' da consulta
    $stmt = $conexao->prepare("SELECT id, nome, valor FROM planos ORDER BY nome ASC");
    $stmt->execute();
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Usuário P2P</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Criar Novo Usuário P2P</h1>

        <form action="action_salvar_usuario.php" method="POST">
            <div class="form-group">
                <label for="name">Nome / Descrição</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Ex: Cliente João Silva" required>
            </div>
            <div class="form-group">
                <label for="whatsapp">WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" class="form-control" placeholder="(11) 98765-4321">
            </div>
            <div class="form-group">
                <label for="plano">Selecionar Pacote</label>
                <select id="plano" name="plano_id" class="form-control" required>
                    <option value="">-- Escolha um plano --</option>
                    <?php foreach ($planos as $plano): ?>
                        
                        <option value="<?php echo $plano['id']; ?>">
                            <?php echo htmlspecialchars($plano['nome']) . " (R$ " . htmlspecialchars($plano['valor']) . ")"; ?>
                        </option>

                    <?php endforeach; ?>
                </select>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Salvar Usuário</button>
                <a href="index.php" class="btn btn-danger">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>