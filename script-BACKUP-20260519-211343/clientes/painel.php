<?php
session_start();

// Verifica se o usuário está logado, redireciona se não estiver
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

require_once '../api/controles/db.php';

$pdo = conectar_bd();
$info_usuario = null;
$username_logado = $_SESSION['username'];

// Tenta buscar as informações do usuário no banco de dados
if ($pdo) {
    $stmt = $pdo->prepare("SELECT Vencimento FROM clientes WHERE usuario = ?");
    $stmt->execute([$username_logado]);
    $info_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Define valores padrão
$data_vencimento = 'Não disponível';
$status = 'Inativo';

// Verifica os dados retornados do banco
if ($info_usuario && !empty($info_usuario['Vencimento'])) {
    $timestamp_vencimento = strtotime($info_usuario['Vencimento']);
    $timestamp_atual = time();

    if ($timestamp_vencimento > $timestamp_atual) {
        $data_vencimento = date('d/m/Y', $timestamp_vencimento);
        $status = 'Ativo';
    } else {
        $data_vencimento = date('d/m/Y', $timestamp_vencimento) . ' (Vencido)';
        $status = 'Vencido';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://example.com/favicon.ico" type="image/x-icon"> <style>
        :root {
            --cor-principal: #8d8d8e; /* Azul escuro */
            --cor-secundaria: #505660; /* Azul claro */
            --cor-fundo: #f0f4f8;
            --cor-cartao: #ffffff;
            --cor-texto: #334155;
            --cor-sucesso: #22c55e;
            --cor-erro: #ef4444;
            --cor-aviso: #f59e0b;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1d92a0, #6ee7b7);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--cor-texto);
            animation: fadeIn 0.8s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .panel-container {
            width: 100%;
            max-width: 500px;
            background-color: var(--cor-cartao);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            animation: slideIn 0.6s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }
        h2 {
            color: var(--cor-principal);
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }
        .logout-link {
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.2s;
        }
        .logout-link:hover {
            color: var(--cor-erro);
        }
        .info-card {
            background-color: var(--cor-fundo);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            text-align: left;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .info-card:hover {
            transform: translateY(-3px);
        }
        .info-card h3 {
            font-size: 18px;
            color: #1e293b;
            margin: 0 0 10px 0;
            font-weight: 600;
        }
        .info-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--cor-principal);
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            color: var(--cor-cartao);
            margin-left: 10px;
        }
        .status-ativo { background-color: var(--cor-sucesso); }
        .status-vencido { background-color: var(--cor-erro); }
        .status-inativo { background-color: var(--cor-aviso); }

        .renew-button {
            display: inline-block;
            margin-top: 30px;
            padding: 15px 40px;
            background-color: var(--cor-principal);
            color: var(--cor-cartao);
            text-decoration: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            transition: background-color 0.3s, transform 0.2s;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        .renew-button:hover {
            background-color: var(--cor-secundaria);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body>
    <div class="panel-container">
        <div class="panel-header">
            <h2>Olá, <?php echo htmlspecialchars($username_logado); ?>!</h2>
            <a href="logout.php" class="logout-link">Sair</a>
        </div>

        <div class="info-card">
            <h3>Status da Conta</h3>
            <span class="info-value">
                <span class="status-badge <?php echo $status == 'Ativo' ? 'status-ativo' : 'status-vencido'; ?>">
                    <?php echo $status; ?>
                </span>
            </span>
        </div>

        <div class="info-card">
            <h3>Vencimento da Assinatura</h3>
            <span class="info-value"><?php echo $data_vencimento; ?></span>
        </div>

        <a href="gerar_pagamento.php" class="renew-button">
            Renovar Assinatura
        </a>
    </div>
</body>
</html>