<?php
// ============================================================
// config/database.php - Configuração do Banco de Dados
// ============================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'crm_agencia');
define('DB_USER', 'root');       // ← Altere para seu usuário MySQL
define('DB_PASS', '');           // ← Altere para sua senha MySQL
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna uma instância PDO (Singleton)
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()
            ]));
        }
    }
    return $pdo;
}
