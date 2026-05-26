<?php
// Inicia a sessão para verificar se o usuário já está logado
session_start();

// Se o usuário já estiver logado, redireciona para o dashboard
if (isset($_SESSION['logged_in_fxtream']) && $_SESSION['logged_in_fxtream'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Carrega a configuração do painel para usar o título e o logo
$config_file = 'config.json';
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
} else {
    $config = [
        'title' => 'PB&CTV',
        'logo_path' => './img/logo_tranparente2.png'
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($config['title']); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($config['logo_path']); ?>">
    
    <!-- Fontes e Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- SweetAlert2 para os alertas -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-color: #d80000;
            --background-color: #dadada;
            --surface-color: #fff;
            --text-color: #14497d;
            --input-bg-color: #ffffff;
            --input-border-color: #00bbff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            overflow: hidden;
        }

        #bg-logo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            object-fit: cover;
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
            user-select: none;
        }

        #particles-js {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 1;
        }

        #particles-js canvas {
            display: block;
            position: absolute;
            top: 0;
            left: 0;
        }

        .login-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-box {
            background: var(--surface-color);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.5);
            width: 100%;
            text-align: center;
        }

        .logo {
            width: 230px;
            height: 230px;
            margin-bottom: 20px;
        }

        .login-box h2 {
            margin-bottom: 30px;
            font-weight: 500;
        }

        .input-group {
            position: relative;
            margin-bottom: 30px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .input-group input {
            width: 100%;
            padding: 10px 10px 10px 45px;
            font-size: 16px;
            color: var(--text-color);
            border: 1px solid var(--input-border-color);
            background-color: var(--input-bg-color);
            border-radius: 5px;
            outline: none;
            transition: border-color 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-button:hover {
            background-color: #0056b3;
        }
        
        .login-button .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>









    <img id="bg-logo" src="<?php echo htmlspecialchars($config['logo_path']); ?>" alt="">
    <div id="particles-js"></div>

    <div class="login-container">
        <div class="login-box"> 
            <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" alt="Logo" class="logo">
            <h2><?php echo htmlspecialchars($config['title']); ?></h2>Painel exclusivo para administradores e revendedores.
            
            
            <form id="login-form"> 
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required placeholder="Usuário">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="Senha">
                </div> 
                <button type="submit" class="login-button">
                    <span class="button-text">ENTRAR</span>
                    <div class="spinner"></div> 
                </button> 
            </form>
        </div>
    </div>


    <!-- Script para o fundo animado -->
    <script>

        
        // Pequena e leve biblioteca de partículas em JS puro
        const particlesContainer = document.getElementById('particles-js');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        if (ctx && particlesContainer) {
            particlesContainer.appendChild(canvas);
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;

            let particlesArray;

            let mouse = { x: null, y: null, radius: (canvas.height / 80) * (canvas.width / 80) };
            window.addEventListener('mousemove', function(event) {
                mouse.x = event.x;
                mouse.y = event.y;
            });

            class Particle {
                constructor(x, y, directionX, directionY, size, color) {
                    this.x = x; this.y = y; this.directionX = directionX;
                    this.directionY = directionY; this.size = size; this.color = color;
                }
                draw() {
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2, false);
                    ctx.fillStyle = 'rgba(100, 150, 255, 0.5)';
                    ctx.fill();
                }
                update() {
                    if (this.x > canvas.width || this.x < 0) { this.directionX = -this.directionX; }
                    if (this.y > canvas.height || this.y < 0) { this.directionY = -this.directionY; }
                    this.x += this.directionX;
                    this.y += this.directionY;
                    this.draw();
                }
            }

            function init() {
                particlesArray = [];
                let numberOfParticles = (canvas.height * canvas.width) / 9000;
                for (let i = 0; i < numberOfParticles; i++) {
                    let size = (Math.random() * 2) + 1;
                    let x = (Math.random() * ((innerWidth - size * 2) - (size * 2)) + size * 2);
                    let y = (Math.random() * ((innerHeight - size * 2) - (size * 2)) + size * 2);
                    let directionX = (Math.random() * 2) - 1;
                    let directionY = (Math.random() * 2) - 1;
                    particlesArray.push(new Particle(x, y, directionX, directionY, size));
                }
            }

            function animate() {
                ctx.clearRect(0, 0, innerWidth, innerHeight);
                for (let i = 0; i < particlesArray.length; i++) {
                    particlesArray[i].update();
                }
                requestAnimationFrame(animate);
            }

            init();
            animate();

            window.addEventListener('resize', function() {
                canvas.width = innerWidth;
                canvas.height = innerHeight;
                mouse.radius = (canvas.height / 80) * (canvas.width / 80);
                init();
            });
        }
    </script>
    
    <!-- Script para o formulário de login -->
    <script>
        document.getElementById('login-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const button = this.querySelector('.login-button');
            const buttonText = button.querySelector('.button-text');
            const spinner = button.querySelector('.spinner');

            buttonText.style.display = 'none';
            spinner.style.display = 'block';

            const formData = new FormData(this);

            fetch('api/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.icon === 'success') {
                    Swal.fire({
                        title: data.title,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = data.url;
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: data.title,
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    title: 'Erro de Conexão',
                    text: 'Não foi possível se comunicar com o servidor.',
                    icon: 'error'
                });
            })
            .finally(() => {
                buttonText.style.display = 'block';
                spinner.style.display = 'none';
            });
        });
    </script>

</body>
</html>
