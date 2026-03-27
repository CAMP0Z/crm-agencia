<?php
// ============================================================
// config/app.php - Configuração Global da Aplicação
// ============================================================

// URL base do sistema (sem barra no final)
define('BASE_URL', 'http://localhost/crm-app');

// Nome do sistema
define('APP_NAME', 'CRM Agência');
define('APP_VERSION', '1.0.0');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload dos arquivos de configuração
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/functions.php';
