<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Área do Cliente - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="single-card-container">
        <div class="single-card">
            <h1 class="title">Área do Cliente</h1>
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert-danger"><?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?></div>
            <?php endif; ?>
            <form action="login_action.php" method="POST">
                <div class="form-group">
                    <input type="text" name="usuario" class="form-control" placeholder="Usuário" required>
                </div>
                <div class="form-group">
                    <input type="password" name="senha" class="form-control" placeholder="Senha" required>
                </div>
                <button type="submit" class="btn-full">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>