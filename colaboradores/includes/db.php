<?php
// Conexão com o banco de dados do Painel de Colaboradores.
// Credenciais criadas no hPanel da Hostinger (Bancos de Dados MySQL) -
// troque os valores abaixo pelos que a Hostinger gerar.

define('DB_HOST', 'localhost');
define('DB_NAME', 'TROCAR_nome_do_banco');
define('DB_USER', 'TROCAR_usuario_do_banco');
define('DB_PASS', 'TROCAR_senha_do_banco');

function conectarBanco(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
