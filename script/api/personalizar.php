<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$config_file = '../config.json';

// Função para ler o arquivo de configuração
function read_config($file) {
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return ['title' => 'TOP IPTV', 'logo_path' => './img/logo_tranparente2.png'];
}

// Função para salvar o arquivo de configuração
function save_config($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

$config = read_config($config_file);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update_title') {
        if (isset($_POST['titulo']) && !empty($_POST['titulo'])) {
            $config['title'] = htmlspecialchars($_POST['titulo']);
            save_config($config_file, $config);
            echo json_encode(["status" => "success", "message" => "Título do site atualizado com sucesso!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "O campo de título não pode ser vazio."]);
        }
    } elseif ($action === 'update_logo') {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['logo']['tmp_name'];
            $file_name = basename($_FILES['logo']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['png', 'jpg', 'jpeg', 'gif', 'svg'];

            if (in_array($file_ext, $allowed_exts)) {
                $target_dir = '../img/';
                $new_logo_name = 'logo.' . $file_ext;
                $target_path = $target_dir . $new_logo_name;

                if (move_uploaded_file($file_tmp_path, $target_path)) {
                    $config['logo_path'] = './img/' . $new_logo_name;
                    save_config($config_file, $config);
                    echo json_encode(["status" => "success", "message" => "Logo atualizado com sucesso!"]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Erro ao mover o arquivo enviado."]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Formato de arquivo não suportado. Use PNG, JPG, JPEG, GIF ou SVG."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Nenhum arquivo de logo foi enviado."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Ação inválida."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Requisição inválida."]);
}
?>