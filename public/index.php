<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/Mailer.php';
require_once __DIR__ . '/../app/helpers/UuidHelper.php';

$BASE_PATH = '/animalSOS/public';

/**
 * Lê o path atual (sem querystring)
 */
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

/**
 * Remove o BASE_PATH do começo do path, se existir
 */
if ($BASE_PATH !== '' && str_starts_with($uriPath, $BASE_PATH)) {
    $uriPath = substr($uriPath, strlen($BASE_PATH));
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
    '/cadastro'  => ['AuthController', 'registro'],
    '/logout'    => ['AuthController', 'logout'],
    '/esqueci'            => ['AuthController', 'esqueciSenha'],
    '/redefinir-senha'    => ['AuthController', 'redefinirSenha'],
    '/salvar-nova-senha'  => ['AuthController', 'salvarNovaSenha'],

    '/home'      => ['PaginasController', 'home'],
    '/sobre'     => ['PaginasController', 'sobre'],

    '/dashboard' => ['EstatisticasController', 'index'],

    '/mapa'      => ['MapaController', 'index'],

    '/perfil'    => ['UsuarioController', 'perfil'],
    '/senha'     => ['UsuarioController', 'senha'],

    '/denuncias' => ['AnimalController', 'listar'],
    '/reportar'  => ['AnimalController', 'reportar'],

    // Comentários (POST)
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
     * - /denuncias/{id} -> detalhes
     * - /denuncias/{id}/status -> atualizar status
     */
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));

    if (count($segments) === 2 && $segments[0] === 'denuncias') {
        $controllerClass = 'AnimalController';
        $action = 'detalhes';
        $params['id'] = $segments[1];
    } elseif (count($segments) === 3 && $segments[0] === 'denuncias' && $segments[2] === 'status') {
        $controllerClass = 'StatusController';
        $action = 'atualizar';
        $params['id'] = $segments[1];
    } else {
        /**
         * 3) Fallback: modo antigo (?c=...&a=...)
         * Isso garante compatibilidade caso você ainda tenha links antigos nas views.
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