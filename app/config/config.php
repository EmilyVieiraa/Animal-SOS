<?php

declare(strict_types=1);

$envConfig = static function (string $chave, ?string $padrao = null): ?string {
    $valor = $_ENV[$chave] ?? $_SERVER[$chave] ?? getenv($chave);

    if ($valor === false || $valor === null || $valor === '') {
        return $padrao;
    }

    return (string)$valor;
};

// Ambiente (apenas local|prod para manter contrato estável)
$appEnv = mb_strtolower((string)$envConfig('APP_ENV', 'local'));
define('APP_ENV', $appEnv === 'prod' ? 'prod' : 'local');

// Erros (dev vs prod)
if (APP_ENV === 'prod') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// URL Base (evite barra final para não gerar // nos links)
$baseUrlPadrao = 'http://localhost/animalSOS/public';
$baseUrlConfigurada = (string)$envConfig('BASE_URL', $baseUrlPadrao);
$baseUrlNormalizada = rtrim($baseUrlConfigurada, '/');

if ($baseUrlNormalizada === '') {
    $baseUrlNormalizada = $baseUrlPadrao;
}

define('BASE_URL', $baseUrlNormalizada);

// Caminho base da aplicação, extraído de BASE_URL automaticamente.
// Usado pelo roteador para remover o prefixo de instalação do REQUEST_URI.
// Evita duplicidade: alterar BASE_URL já atualiza o roteador.
define('APP_BASE_PATH', (string)(parse_url(BASE_URL, PHP_URL_PATH) ?: ''));

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
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
define('MAIL_HOST', $envConfig('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int)$envConfig('MAIL_PORT', '587'));
define('MAIL_USER', $envConfig('MAIL_USER', ''));
define('MAIL_PASS', $envConfig('MAIL_PASS', ''));
define('MAIL_FROM', $envConfig('MAIL_FROM', MAIL_USER !== '' ? MAIL_USER : 'no-reply@localhost'));
define('MAIL_FROM_NAME', $envConfig('MAIL_FROM_NAME', 'Animal S.O.S'));

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
        APP_PATH . 'helpers/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
