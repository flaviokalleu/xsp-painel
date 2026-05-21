<?php
/**
 * Função para estabelecer a conexão com o banco de dados usando PDO.
 * @return PDO|null Retorna o objeto PDO de conexão ou null em caso de falha.
 */
function conectar_bd() {
    // Suas informações de conexão
    $endereco = 'localhost';
    $banco = 'u874781703_painelags';
    $dbusuario = 'u874781703_painelags';
    $dbsenha = 'A82838188Agno@';

    try {
        // Cria a instância do PDO para conectar ao MySQL
        $conexao = new PDO("mysql:host=$endereco;dbname=$banco;charset=utf8mb4", $dbusuario, $dbsenha);
        
        // Configura o PDO para lançar exceções em caso de erros (muito útil para depuração)
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Configura o PDO para buscar dados como array associativo por padrão
        $conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $conexao;
    } catch(PDOException $e) {
        // Em caso de falha na conexão, loga o erro no servidor
        error_log('Erro na conexao com o banco de dados: ' . $e->getMessage());
        
        // Em um ambiente de produção, é melhor não expor a mensagem de erro ao usuário final
        // Você pode retornar null ou lançar uma exceção personalizada.
        return null;
    }
}
?>
