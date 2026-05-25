<?php
session_start();
require_once("chatbot_integrado_funcoes.php");

// Protege a página, verifica se o cliente está logado
isClienteLogged(); 

$cliente = getClienteLogado();

if (!$cliente) {
    // Se não encontrar os dados do cliente, faz logout por segurança
    header("Location: login_cliente.php?logout=1");
    exit;
}

// Busca os detalhes do plano do cliente
$plano = getPlanoDoCliente($cliente['id']);

$vencimento_obj = new DateTime($cliente['Vencimento']);
$hoje_obj = new DateTime();
$status_classe = $vencimento_obj > $hoje_obj ? 'text-success' : 'text-danger';
$status_texto = $vencimento_obj > $hoje_obj ? 'Ativo' : 'Vencido';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Cliente - PB&CTV</title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Bem-vindo, <?php echo htmlspecialchars($cliente['name'] ?: $cliente['usuario']); ?>!</h3>
            <a href="login_cliente.php?logout=1" class="btn btn-sm btn-outline-danger">Sair</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Status da sua Assinatura</h4>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Usuário:</strong> <?php echo htmlspecialchars($cliente['usuario']); ?></li>
                    <li class="list-group-item"><strong>Plano:</strong> <?php echo htmlspecialchars($plano['nome'] ?? 'Não definido'); ?></li>
                    <li class="list-group-item"><strong>Valor:</strong> R$ <?php echo number_format($plano['valor'] ?? 0, 2, ',', '.'); ?></li>
                    <li class="list-group-item"><strong>Vencimento:</strong> <?php echo $vencimento_obj->format('d/m/Y H:i'); ?></li>
                    <li class="list-group-item"><strong>Status:</strong> <span class="<?php echo $status_classe; ?> fw-bold"><?php echo $status_texto; ?></span></li>
                </ul>
            </div>
            <div class="card-footer text-center">
                <?php if ($plano && $plano['valor'] > 0): ?>
                    <a href="pagamento.php" class="btn btn-success btn-lg">
                        <i class="fas fa-dollar-sign"></i> Renovar Agora (R$ <?php echo number_format($plano['valor'], 2, ',', '.'); ?>)
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg" disabled>
                        Renovação indisponível (Plano sem valor definido)
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
