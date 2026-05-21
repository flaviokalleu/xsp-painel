<?php
session_start();

if (isset($_SESSION['username'])) {
    header('Location: painel.php');
    exit();
}

require_once '../api/controles/db.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $erro = 'Por favor, preencha o usuário e a senha.';
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $pdo = conectar_bd();
        if ($pdo) {
            // CORREÇÃO FINAL: Nomes de colunas ('id', 'usuario', 'senha') e tabela ('clientes')
            $stmt = $pdo->prepare("SELECT id, usuario FROM clientes WHERE usuario = ? AND senha = ?");
            $stmt->execute([$username, $password]);
            $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario_db) {
                // CORREÇÃO FINAL: Usando as chaves corretas do array ('id', 'usuario')
                $_SESSION['user_id'] = $usuario_db['id'];
                $_SESSION['username'] = $usuario_db['usuario'];
                header('Location: painel.php');
                exit();
            } else {
                $erro = 'Usuário ou senha inválidos.';
            }
        } else {
            $erro = 'Erro ao conectar com o servidor. Tente novamente mais tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - Área do Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #004d40;
            --secondary-color: #00897b;
            --background-color: #f0f2f5;
            --card-background: #ffffff;
            --text-color: #333;
            --error-color: #d32f2f;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e0f7fa, #c5e1a5);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: var(--text-color);
        }

        .login-container {
            background-color: var(--card-background);
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 380px;
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            font-size: 2em;
            margin-bottom: 25px;
            color: var(--primary-color);
            font-weight: 600;
        }

        input[type="text"], input[type="password"] {
            width: calc(100% - 20px);
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 5px rgba(0, 137, 123, 0.2);
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            margin-top: 15px;
            transition: background-color 0.3s, transform 0.2s;
        }

        button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .error {
            color: var(--error-color);
            font-size: 0.9em;
            margin-top: 15px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Área do Cliente</h2>
        <form action="login.php" method="post">
            <input type="text" name="username" placeholder="Seu Usuário" required>
            <input type="password" name="password" placeholder="Sua Senha" required>
            <button type="submit">Entrar</button>
        </form>
        <?php if (!empty($erro)): ?>
            <p class="error"><?php echo $erro; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>