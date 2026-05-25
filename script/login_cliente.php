<?php
session_start();
require_once("api/controles/db.php");
require_once("chatbot_integrado_funcoes.php"); // Usaremos funções daqui

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'], $_POST['senha'])) {
    if (loginCliente($_POST['usuario'], $_POST['senha'])) {
        header("Location: painel_cliente.php");
        exit;
    } else {
        $feedback = "<div class='alert alert-danger'>Usuário ou senha inválidos.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Cliente - PB&CTV</title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f0f2f5;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">Área do Cliente</h3>
            <?php echo $feedback; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
