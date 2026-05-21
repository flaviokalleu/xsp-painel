<?php
// ARQUIVO: /gerenciador/salvar_filme.php

header('Content-Type: application/json'); // A resposta para o frontend será em JSON

// 1. Incluir seu arquivo de conexão com o banco de dados
// Usamos '../' para "subir" um nível de diretório, saindo de 'gerenciador'
// para depois entrar em 'api/controles/'
require_once('../api/controles/db.php');

// 2. Chamar sua função para obter a conexão
$pdo = conectar_bd();

// 3. Verificar se a conexão foi bem-sucedida
if (!$pdo) {
    // Se a conexão falhou, sua função retorna null. Então, encerramos o script.
    echo json_encode(['success' => false, 'message' => 'Erro fatal: Nao foi possivel conectar ao banco de dados.']);
    exit;
}

// 4. Receber os dados do formulário
// Os dados virão via POST do seu painel.
// Usamos '??' como um atalho para definir um valor padrão caso a variável não exista.
$name = $_POST['name'] ?? null;
$tmdb_id = $_POST['tmdb_id'] ?? null;
$year = $_POST['year'] ?? null;
$plot = $_POST['plot'] ?? null;
$cast = $_POST['cast'] ?? null;
$director = $_POST['director'] ?? null;
$genre = $_POST['genre'] ?? null;
$cover_url = $_POST['cover_url'] ?? null; // URL da imagem de capa/pôster
$link = $_POST['link'] ?? null; // O link do stream que você vai inserir manualmente
$category_id = $_POST['category_id'] ?? null;

// Validação simples: nome, link e categoria são essenciais
if (empty($name) || empty($link) || empty($category_id)) {
    echo json_encode(['success' => false, 'message' => 'Erro: Nome, Link do Stream e Categoria sao obrigatorios.']);
    exit;
}

// 5. Preparar e executar a inserção no banco de dados
// Usar Prepared Statements é a forma MAIS SEGURA de interagir com o banco de dados.
$sql = "INSERT INTO streams (
            `name`, `tmdb_id`, `year`, `plot`, `cast`, `director`, 
            `genre`, `stream_icon`, `link`, `category_id`, 
            `stream_type`, `added`
        ) VALUES (
            :name, :tmdb_id, :year, :plot, :cast, :director, 
            :genre, :stream_icon, :link, :category_id, 
            'movie', :added
        )";

try {
    $stmt = $pdo->prepare($sql);

    // Associa os valores às variáveis da query SQL
    $stmt->execute([
        ':name' => $name,
        ':tmdb_id' => $tmdb_id,
        ':year' => $year,
        ':plot' => $plot,
        ':cast' => $cast,
        ':director' => $director,
        ':genre' => $genre,
        ':stream_icon' => $cover_url, // Usando o campo stream_icon para o pôster
        ':link' => $link,
        ':category_id' => $category_id,
        ':added' => time() // Adiciona a data/hora atual no formato timestamp
    ]);

    // 6. Enviar uma resposta de sucesso
    echo json_encode(['success' => true, 'message' => 'Filme "' . $name . '" adicionado com sucesso!']);

} catch (PDOException $e) {
    // 7. Enviar uma resposta de erro, caso a inserção falhe
    // Log do erro real para debug
    error_log('Erro ao inserir filme no BD: ' . $e->getMessage()); 
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco de dados. Verifique os logs do servidor.']);
}

?>