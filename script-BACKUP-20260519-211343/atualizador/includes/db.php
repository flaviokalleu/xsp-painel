<?php
// includes/db.php

function conectar_banco() {
    // --- PREENCHA COM SEUS DADOS ---
    $host = 'localhost';
    $dbname = 'u874781703_painelags'; // O nome do seu banco de dados
    $user = 'u874781703_painelags';
    $pass = 'A82838188Agno@';
    // --------------------------------

    try {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ];
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Se a conexão falhar, o script para e mostra o erro.
        die("ERRO DE CONEXÃO COM O BANCO DE DADOS: " . $e->getMessage());
    }
}
?>