<?php

declare(strict_types=1);

// Ambiente
define('APP_ENV', 'local'); // local | prod

// Erros (dev vs prod)
if (APP_ENV === 'prod') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// URL Base (evite barra final para não dar // nos links)
define('BASE_URL', 'http://localhost:8000');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');        // ADICIONADO (seu Database usa isso)
define('DB_NAME', 'animal_sos');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Diretórios (ajuste conforme sua estrutura real)
define('APP_PATH', dirname(__DIR__) . '/');                 // .../app/
define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');       // raiz do projeto
define('PUBLIC_PATH', ROOT_PATH . 'public/');
define('UPLOAD_PATH', PUBLIC_PATH . 'uploads/');

// Sessão
define('SESSION_NAME', 'animal_sos_session');
define('SESSION_LIFETIME', 3600); // 1 hora (pode usar depois para expirar login)

// Upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Paginação
define('ITEMS_PER_PAGE', 10);

// Configurações de E-mail (SMTP)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'emilycamilavieira7@gmail.com');
define('MAIL_PASS', 'qxhwiyswsirtgizb');
define('MAIL_FROM', 'emilycamilavieira7@gmail.com');
define('MAIL_FROM_NAME', 'Animal S.O.S');

// Iniciar sessão (com proteção)
session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload de classes (inclui core/ também, recomendado em MVC)
spl_autoload_register(function (string $class): void {
    $paths = [
        APP_PATH . 'core/',
        APP_PATH . 'controllers/',
        APP_PATH . 'models/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
