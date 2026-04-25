<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';  // define constantes, sessão e autoload
require_once __DIR__ . '/../app/config/database.php'; // singleton PDO (espera config.php já carregado)
require_once __DIR__ . '/../vendor/autoload.php';     // Composer (PHPMailer, Symfony UUID, etc.)
// Mailer.php não precisa de include explícito: o spl_autoload de config.php cobre app/core/.
// UuidHelper é carregado por autoload (app/helpers).

// Caminho base derivado de BASE_URL (definido em config.php).
// Centraliza a configuração: alterar BASE_URL já atualiza o roteador automaticamente.
$caminhoBase = APP_BASE_PATH;

/**
 * Lê o path atual (sem querystring)
 */
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

/**
 * Remove o prefixo de instalação do path, se existir.
 * Ex.: /animalSOS/public/denuncias → /denuncias
 */
if ($caminhoBase !== '' && str_starts_with($uriPath, $caminhoBase)) {
    $uriPath = substr($uriPath, strlen($caminhoBase));
    if ($uriPath === '') $uriPath = '/';
}

/**
 * Normaliza: "/" ou "/denuncias/123"
 */
$path = '/' . trim($uriPath, '/');
if ($path === '//') $path = '/';

/**
 * Define Controller/Action padrão
 */
$controllerClass = 'AuthController';
$action          = 'login';
$params          = [];

/**
 * Rotas amigáveis (fixas)
 */
$routes = [
    '/' => ['PaginasController', 'home'],
    '/login'     => ['AuthController', 'login'],
    '/logout'    => ['AuthController', 'logout'],
    '/esqueci'            => ['AuthController', 'esqueciSenha'],
    '/redefinir-senha'    => ['AuthController', 'redefinirSenha'],
    '/salvar-nova-senha'  => ['AuthController', 'salvarNovaSenha'],

    // [LEGADO] /home é alias de /. Manter até confirmação de que não há links externos.
    '/home'      => ['PaginasController', 'home'],

    '/perfil'    => ['UsuarioController', 'meuPerfil'],
    // [LEGADO] /senha é alias de /esqueci. Manter até confirmação de que não há links ou e-mails antigos.
    '/senha'     => ['AuthController', 'esqueciSenha'],

    '/denuncias' => ['AnimalController', 'listar'],
    '/reportar'  => ['AnimalController', 'reportar'],

    // POST
    '/comentarios' => ['ComentarioController', 'adicionar'],
];

/**
 * 1) Match de rota fixa
 */
if (isset($routes[$path])) {
    [$controllerClass, $action] = $routes[$path];
} else {
    /**
     * 2) Rotas dinâmicas
     * - /denuncias/{id} → detalhes do animal
     */
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));

    if (count($segments) === 2 && $segments[0] === 'denuncias') {
        $controllerClass = 'AnimalController';
        $action = 'detalhes';
        $params['id'] = $segments[1];
    } else {
        /**
         * 3) [LEGADO] Fallback query-string (?c=...&a=...)
         *
         * Mantido por compatibilidade enquanto existirem links ?c=...&a=... nas views ou
         * nos e-mails transacionais gerados pelo sistema.
         * Condição para remoção: confirmar que nenhuma view, e-mail ou redirect usa
         * query-string com c/a, e que todas as rotas amigáveis acima as cobrem.
         */
        $c = $_GET['c'] ?? null;
        $a = $_GET['a'] ?? null;

        if ($c && $a) {
            $controllerClass = ucfirst(strtolower((string)$c)) . 'Controller';
            $action = (string)$a;
        } else {
            // Se não bater em nada, 404
            http_response_code(404);
            echo 'Rota não encontrada.';
            exit;
        }
    }
}

/**
 * Injeta params (id) no $_GET para reutilizar seus controllers sem alterações
 */
foreach ($params as $k => $v) {
    $_GET[$k] = $v;
}

/**
 * Carrega controller e executa action
 */
$controllerFile = APP_PATH . 'controllers/' . $controllerClass . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    echo "Controller não encontrado.";
    exit;
}

require_once $controllerFile;

if (!class_exists($controllerClass)) {
    http_response_code(500);
    echo "Classe do controller inválida.";
    exit;
}

$controller = new $controllerClass();

if (!method_exists($controller, $action)) {
    http_response_code(404);
    echo "Ação não encontrada.";
    exit;
}

$controller->{$action}();