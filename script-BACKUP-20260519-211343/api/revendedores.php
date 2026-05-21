<?php
session_start();
require_once('./controles/db.php');
require_once('./controles/revendedores.php');
require_once('./controles/checkLogout.php');
header('Content-Type: application/json; charset=utf-8');

checkLogoutapi();

// Ação para o Admin Principal editar suas próprias credenciais
if (isset($_POST['edite_admin'])) {
    if (function_exists('edite_admin')) {
        echo json_encode(edite_admin());
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função edite_admin não encontrada!', 'icon' => 'error']);
    }
    exit();
}

if (isset($_POST['confirme_edite_admin'])) {
    $usuario = $_POST["usuario"] ?? null;
    $senha = $_POST["senha"] ?? null;
    if (function_exists('confirme_edite_admin')) {
        echo json_encode(confirme_edite_admin($usuario, $senha));
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função confirme_edite_admin não encontrada!', 'icon' => 'error']);
    }
    exit();
}

// Ação para um Revendedor alterar a própria senha
if (isset($_POST['edite_admin_revenda'])) {
    if (function_exists('edite_admin_revenda')) {
        echo json_encode(edite_admin_revenda());
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função edite_admin_revenda não encontrada!', 'icon' => 'error']);
    }
    exit();
}

if (isset($_POST['confirme_edite_admin_revenda'])) {
    $senha_atual = $_POST["senha_atual"] ?? null;
    $nova_senha = $_POST["nova_senha"] ?? null;
    $confirme_senha = $_POST["confirme_senha"] ?? null;

    if ($nova_senha !== $confirme_senha) {
        echo json_encode(['title' => 'Erro!', 'msg' => 'As senhas digitadas não são iguais.', 'icon' => 'error']);
        exit();
    }
    
    if (function_exists('confirme_edite_admin_revenda')) {
        echo json_encode(confirme_edite_admin_revenda($senha_atual, $nova_senha));
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função confirme_edite_admin_revenda não encontrada!', 'icon' => 'error']);
    }
    exit();
}


// --- SUAS OUTRAS FUNÇÕES ORIGINAIS DE REVENDEDOR ---

if (isset($_POST['edite_revendedor'])) {
    $id = $_POST['edite_revendedor'];
    if (function_exists('edite_revendedor')) {
        echo json_encode(edite_revendedor($id));
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função edite_revendedor não encontrada!', 'icon' => 'error']);
    }
    exit();
}

if (isset($_POST['confirme_edite_revendedor'])) {
    $id = $_POST["confirme_edite_revendedor"] ?? null;
    $usuario = $_POST["usuario"] ?? null;
    $senha = $_POST["senha"] ?? null;
    $plano = $_POST["plano"] ?? null;
    if (function_exists('confirme_editar_revendedor')) {
        echo json_encode(confirme_editar_revendedor($id, $usuario, $senha, $plano));
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função confirme_editar_revendedor não encontrada!', 'icon' => 'error']);
    }
    exit();
}

if (isset($_POST['adicionar_creditos'])) {
    $id = $_POST['adicionar_creditos'];
    $usuario = $_POST['usuario'] ?? null;
    if (function_exists('add_creditos')) {
        echo json_encode(add_creditos($id, $usuario));
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função add_creditos não encontrada!', 'icon' => 'error']);
    }
    exit();
}

if (isset($_POST['confirme_add_creditos'])) {
    $id = $_POST['confirme_add_creditos'];
    $creditos = (int)($_POST["creditos"] ?? 0);
    if (function_exists('confirme_add_creditos')) {
        echo json_encode(confirme_add_creditos($id, $creditos));
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função confirme_add_creditos não encontrada!', 'icon' => 'error']);
    }
    exit();
}

if (isset($_POST['add_revendedor'])) {
    if (function_exists('add_revendedor')) {
        echo json_encode(add_revendedor());
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função add_revendedor não encontrada!', 'icon' => 'error']);
    }
    exit();
}

if (isset($_POST['confirme_add_revendedor'])) {
    $usuario = $_POST["usuario"] ?? null;
    $senha = $_POST["senha"] ?? null;
    $plano = $_POST["plano"] ?? null;
    if (function_exists('confirme_add_revendedor')) {
        echo json_encode(confirme_add_revendedor($usuario, $senha, $plano));
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função confirme_add_revendedor não encontrada!', 'icon' => 'error']);
    }
    exit();
}

if (isset($_POST['delete_revendedor'])) {
    $id = $_POST['delete_revendedor'];
    $usuario = $_POST['usuario'] ?? null;
    if (function_exists('delete_revendedor')) {
        echo json_encode(delete_revendedor($id, $usuario));
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função delete_revendedor não encontrada!', 'icon' => 'error']);
    }
    exit();
}

if (isset($_POST['confirme_delete_revendedor'])) {
    $id = $_POST['confirme_delete_revendedor'];
    if (function_exists('confirme_delete_revendedor')) {
        echo json_encode(confirme_delete_revendedor($id));
    } else {
        echo json_encode(['title' => 'Erro!', 'msg' => 'Função confirme_delete_revendedor não encontrada!', 'icon' => 'error']);
    }
    exit();
}
?>